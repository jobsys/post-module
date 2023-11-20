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

		<HomologyBox
			ref="homologyBoxRef"
			homologies-route="api.manager.post.homology"
			homology-edit-route="api.manager.post.edit"
			homology-item-route="api.manager.post.item"
			homology-delete-route="api.manager.post.delete"
			:homology-columns="getHomologyColumns"
			:homology-form-items="getHomologyForm"
		></HomologyBox>
	</div>
</template>

<script setup>
import { h, inject, onMounted, reactive, ref } from "vue"
import { useTableActions, useTableImage } from "jobsys-newbie"
import { useFetch, useModalConfirm, useProcessStatusSuccess } from "jobsys-newbie/hooks"
import { message } from "ant-design-vue"
import { router, usePage } from "@inertiajs/vue3"
import { DeleteOutlined, EditOutlined, PictureOutlined, PlusOutlined, UnorderedListOutlined } from "@ant-design/icons-vue"
import CategoryBox from "@modules/Starter/Resources/views/web/components/CategoryBox.vue"
import { findIndex } from "lodash-es"
import HomologyBox from "@modules/Starter/Resources/views/web/components/HomologyBox.vue"

const props = defineProps({
	categoryOptions: {
		type: Array,
		default: () => [],
	},
})
const route = inject("route")
const auth = inject("auth")

const tableRef = ref()
const homologyBoxRef = ref()

const state = reactive({
	showCategoryDrawer: false,
	showEditorModal: false,
	url: "",
})

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
		`该操作会同时删除其它语言版本，您确认要删除 ${item.title} 吗？`,
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

const getForm = () => [
	{
		title: "所属分类",
		key: "category_id",
		type: "select",
		required: true,
		width: 200,
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
		title: "标识",
		key: "slug",
		help: "标识不能重复，如果不填，系统将会根据标题自动生成",
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
		title: "发布时间",
		key: "published_at",
		type: "date",
		help: "该时间主要用于展示，如果为空则展示创建时间",
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

const columns = () => [
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
		dataIndex: "published_at_date",
	},
	{
		title: "创建时间",
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

				homologyBoxRef.value?.useHomologyAction(actions, record)
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

const getHomologyForm = (currentHomology) => {
	const formItems = getForm()
	const categoryIndex = findIndex(formItems, { key: "category_id" })

	formItems[categoryIndex].disabled = true
	formItems[categoryIndex].defaultValue = currentHomology?.category_id

	return formItems
}

const getHomologyColumns = () => [
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
]

onMounted(() => {
	const create = usePage().props?.query?.create
	if (create) {
		onEdit()
	}
})
</script>
