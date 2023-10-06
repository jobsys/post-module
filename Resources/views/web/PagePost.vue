<template>
	<div class="page-post">
		<NewbieTable ref="tableRef" :url="route('api.manager.post.items')" :columns="columns()" row-key="id">
			<template #prepend>
				<NewbieButton
					v-auth="'api.manager.post.group.items'"
					:icon="h(UnorderedListOutlined)"
					type="primary"
					@click="state.showCategoryDrawer = true"
				>
					新闻分类管理
				</NewbieButton>
			</template>

			<template #functional>
				<NewbieButton v-auth="'api.manager.post.edit'" type="primary" :icon="h(PlusOutlined)" @click="onEdit(false)"
					>新增新闻资讯
				</NewbieButton>
			</template>
		</NewbieTable>

		<NewbieModal
			v-model:visible="state.showEditorModal"
			title="新闻资讯编辑"
			:width="1200"
			:modal-props="{ bodyStyle: { height: '600px', overflow: 'auto' } }"
		>
			<NewbieForm
				ref="edit"
				:fetch-url="state.url"
				:auto-load="!!state.url"
				:submit-url="route('api.manager.post.edit')"
				:card-wrapper="false"
				:form="getForm()"
				:form-props="{ labelCol: { span: 4 }, wrapperCol: { span: 19 } }"
				:close="closeEditorModal"
				@success="closeEditorModal(true)"
			/>
		</NewbieModal>

		<NewbieModal type="drawer" v-model:visible="state.showCategoryDrawer" title="新闻分类管理" :width="1200" @close="onCloseCategoryDrawer">
			<CategoryBox module="post" group="news"></CategoryBox>
		</NewbieModal>

		<NewbieModal v-model:visible="state.showHomologyModal" title="分类多语言管理" type="drawer" :width="1200">
			<NewbieTable
				ref="homologyTableRef"
				:pagination="false"
				:filterable="false"
				:after-fetched="onAfterHomologyTableFetch"
				:url="route('api.manager.post.homology', { id: state.currentHomology?.id })"
				:columns="getHomologyColumns()"
			>
				<template #functional>
					<a-dropdown>
						<template #overlay>
							<a-menu @click="({ key }) => onHomologyEdit({ lang: key })">
								<a-menu-item v-for="lang in foreignLangOptions" :key="lang.value">
									{{ lang.label }}
								</a-menu-item>
							</a-menu>
						</template>
						<a-button type="primary">
							新增语言版本
							<DownOutlined />
						</a-button>
					</a-dropdown>
				</template>
			</NewbieTable>
		</NewbieModal>

		<NewbieModal
			v-model:visible="state.showHomologyEditorModal"
			title="分类编辑"
			:modal-props="{ bodyStyle: { height: '600px', overflow: 'auto' } }"
		>
			<NewbieForm
				ref="formHomologyRef"
				:submit-url="route('api.manager.post.edit')"
				:fetch-url="state.url"
				:auto-load="!!state.url"
				:card-wrapper="false"
				:form="getHomologyForm()"
				:form-props="{ labelCol: { span: 4 }, wrapperCol: { span: 19 } }"
				:closable="false"
				:close="closeHomologyEditor"
				:before-submit="onBeforeHomologyFormSubmit"
				@success="closeHomologyEditor(true)"
			/>
		</NewbieModal>
	</div>
</template>

<script setup>
import { computed, h, inject, onMounted, reactive, ref } from "vue"
import { useTableActions, useTableImage } from "jobsys-newbie"
import { useFetch, useLabelFromOptionsValue, useModalConfirm, useProcessStatusSuccess } from "jobsys-newbie/hooks"
import { message } from "ant-design-vue"
import { router, usePage } from "@inertiajs/vue3"
import {
	DeleteOutlined,
	DownOutlined,
	EditOutlined,
	GlobalOutlined,
	PictureOutlined,
	PlusOutlined,
	UnorderedListOutlined,
} from "@ant-design/icons-vue"
import CategoryBox from "@modules/Starter/Resources/views/web/components/CategoryBox.vue"
import useSystemStore from "@manager/stores/system"
import { findIndex } from "lodash-es"

const props = defineProps({
	categoryOptions: {
		type: Array,
		default: () => [],
	},
})
const route = inject("route")
const auth = inject("auth")

const tableRef = ref()
const homologyTableRef = ref()

const systemStore = useSystemStore()

const defaultLang = computed(() => systemStore.lang.defaultLang)
const langOptions = computed(() => systemStore.lang.langOptions)
const foreignLangOptions = computed(() => langOptions.value.filter((item) => item.value !== defaultLang.value))

const state = reactive({
	showCategoryDrawer: false,
	showEditorModal: false,
	showGroupEditorModal: false,
	currentHomology: null,
	showHomologyEditorModal: false,
	currentLang: undefined,
	url: "",
})

const onAfterHomologyTableFetch = (res) => {
	return {
		items: res.result,
	}
}

const getForm = () => [
	{
		title: "所属分类",
		key: "category_id",
		type: "select",
		required: true,
		options: props.categoryOptions,
	},
	{
		title: "标题",
		key: "title",
		required: true,
		style: {
			width: "500px",
		},
	},
	{
		title: "分类标识",
		key: "slug",
		help: "分类标识不能重复，如果不填，系统将会根据标题自动生成",
	},
	{
		key: "cover",
		title: "封面",
		type: "uploader",
		help: "不超过10M",
		defaultProps: {
			accept: ".png,.jpg,.jpeg",
			action: route("api.manager.tool.uploadFile"),
			maxSize: 10,
			type: "picture-card",
			multipart: true,
		},
	},
	{
		title: "摘要",
		key: "brief",
		type: "textarea",
	},
	{
		title: "内容",
		key: "content",
		type: "editor",
		required: true,
	},
	{
		key: "attachments",
		title: "附件",
		type: "uploader",
		help: "不超过10M",
		defaultProps: {
			accept: ".png,.jpg,.jpeg,.doc,.docx,.ppt,.pptx,.zip,.rar,.pdf,.xls,.xlsx",
			action: route("api.manager.tool.uploadFile"),
			maxSize: 10,
			maxNum: 5,
			type: "text",
			multipart: true,
		},
	},
	{
		title: "是否置顶",
		key: "is_top",
		type: "switch",
	},
	{
		title: "排序",
		key: "sort_order",
		type: "number",
	},
	{
		title: "显示状态",
		key: "is_active",
		type: "switch",
		defaultValue: true,
	},
]

const onEdit = (item) => {
	state.url = item ? route("api.manager.post.item", { id: item.id }) : ""
	state.showEditorModal = true
}

const closeEditorModal = (isRefresh) => {
	if (isRefresh) {
		tableRef.value.doFetch()
	}
	state.showEditorModal = false
}

const onCloseCategoryDrawer = () => {
	router.reload({ only: ["categoryOptions"] })
}

const onDelete = (item) => {
	const modal = useModalConfirm(
		`您确认要删除 ${item.title} 吗？`,
		async () => {
			try {
				const res = await useFetch().post(route("api.manager.post.delete"), { id: item.id })
				modal.destroy()
				useProcessStatusSuccess(res, () => {
					message.success("删除成功")
					tableRef.value.doFetch()
				})
			} catch (e) {
				modal.destroy(e)
			}
		},
		true,
	)
}

const columns = () => [
	{
		title: "#",
		key: "index",
		width: 50,
		customRender: ({ index }) => h("span", {}, index + 1),
	},
	{
		title: "标题",
		width: 200,
		ellipsis: true,
		dataIndex: "title",
		filterable: true,
	},
	{
		title: "封面",
		width: 120,
		key: "cover",
		align: "center",
		customRender: ({ record }) => useTableImage(record.cover.url, h(PictureOutlined)),
	},
	{
		title: "所属分类",
		width: 200,
		dataIndex: ["category", "name"],
		filterable: {
			key: "category_id",
			type: "select",
			options: props.categoryOptions,
		},
	},
	{
		title: "点击率",
		width: 100,
		dataIndex: "views_count",
		align: "center",
	},

	{
		title: "是否置顶",
		key: "is_top",
		width: 100,
		align: "center",
		customRender: ({ record }) =>
			useTableActions({
				type: "switch",
				name: ["是", "否"],
				value: record.is_top,
			}),
	},
	{
		title: "排序",
		width: 50,
		dataIndex: "sort_order",
		align: "center",
	},
	{
		title: "显示状态",
		key: "is_active",
		width: 100,
		customRender: ({ record }) =>
			useTableActions({
				type: "switch",
				name: ["显示", "隐藏"],
				value: record.is_active,
			}),
	},
	{
		title: "发布时间",
		width: 180,
		ellipsis: true,
		dataIndex: "created_at_datetime",
	},
	{
		title: "操作",
		width: 240,
		fixed: "right",
		customRender: ({ record }) => {
			const actions = []

			if (auth("api.manager.post.edit")) {
				actions.push({
					name: "编辑",
					props: {
						icon: h(EditOutlined),
						size: "small",
					},
					action() {
						onEdit(record)
					},
				})
			}

			if (langOptions.value.length > 1 && record.lang === defaultLang.value) {
				actions.push({
					name: "多语言",
					props: {
						icon: h(GlobalOutlined),
						size: "small",
					},
					action() {
						state.currentHomology = record
						state.showHomologyModal = true
					},
				})
			}

			if (auth("api.manager.post.delete")) {
				actions.push({
					name: "删除",
					props: {
						icon: h(DeleteOutlined),
						size: "small",
					},
					action() {
						onDelete(record)
					},
				})
			}
			return useTableActions(actions)
		},
	},
]

const onBeforeHomologyFormSubmit = ({ formatForm }) => {
	formatForm.lang = state.currentLang
	formatForm.homology_id = state.currentHomology?.id
	formatForm.slug = state.currentHomology?.slug
	return formatForm
}

const onHomologyEdit = ({ lang, item }) => {
	if (item) {
		state.url = route("api.manager.post.item", { id: item.id })
	} else {
		state.url = ""
	}

	state.currentLang = lang || undefined
	state.showHomologyEditorModal = true
}

const closeHomologyEditor = (refresh) => {
	state.showHomologyEditorModal = false
	if (refresh) {
		homologyTableRef.value.doFetch()
	}
}

const getHomologyForm = () => {
	const formItems = getForm()

	formItems.unshift({
		title: "语言版本",
		key: "lang",
		type: "select",
		disabled: true,
		options: langOptions.value,
		defaultValue: state.currentLang,
	})

	const slugIndex = findIndex(formItems, { key: "slug" })
	const categoryIndex = findIndex(formItems, { key: "category_id" })

	formItems[slugIndex].disabled = true
	formItems[slugIndex].defaultValue = state.currentHomology?.slug

	formItems[categoryIndex].disabled = true
	formItems[categoryIndex].defaultValue = state.currentHomology?.category_id

	return formItems
}

const getHomologyColumns = () => {
	return [
		{
			title: "语言版本",
			dataIndex: "lang",
			width: 120,
			customRender({ record }) {
				return h("span", {}, useLabelFromOptionsValue(record.lang, langOptions.value))
			},
		},
		{
			title: "标题",
			dataIndex: "title",
			width: 120,
		},
		{
			title: "标识",
			dataIndex: "slug",
			width: 120,
		},
		{
			title: "操作",
			width: 160,
			key: "operation",
			align: "center",
			fixed: "right",
			customRender({ record }) {
				const actions = []

				actions.push({
					name: "编辑",
					props: {
						icon: h(EditOutlined),
						size: "small",
					},
					action() {
						onHomologyEdit({ item: record })
					},
				})

				actions.push({
					name: "删除",
					props: {
						icon: h(DeleteOutlined),
						size: "small",
					},
					action() {
						onDelete(record)
					},
				})

				return useTableActions(actions)
			},
		},
	]
}

onMounted(() => {
	const create = usePage().props?.query?.create
	if (create) {
		onEdit()
	}
})
</script>
