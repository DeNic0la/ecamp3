<template>
  <dialog-form
    v-model="showDialog"
    :loading="loading"
    :error="error"
    icon="mdi-calendar-plus"
    :title="$tc('components.campAdmin.dialogPeriodCreate.title')"
    :submit-action="createPeriod"
    :submit-label="$tc('global.button.create')"
    submit-color="success"
    :cancel-action="close"
  >
    <template #activator="scope">
      <slot name="activator" v-bind="scope" />
    </template>
    <dialog-period-form :period="entityData" />
  </dialog-form>
</template>

<script>
import DialogForm from '@/components/dialog/DialogForm.vue'
import DialogBase from '@/components/dialog/DialogBase.vue'
import DialogPeriodForm from './DialogPeriodForm.vue'

export default {
  name: 'DialogPeriodCreate',
  components: { DialogForm, DialogPeriodForm },
  extends: DialogBase,
  props: {
    camp: { type: Object, required: true },
  },
  data() {
    return {
      entityProperties: ['camp', 'description', 'start', 'end'],
      entityUri: '',
    }
  },
  watch: {
    showDialog: function (showDialog) {
      if (showDialog) {
        this.setEntityData({
          camp: this.camp._meta.self,
          description: '',
          start: '',
          end: '',
        })
      } else {
        // clear form on exit
        this.clearEntityData()
      }
    },
  },
  mounted() {
    this.api.href(this.api.get(), 'periods').then((uri) => (this.entityUri = uri))
  },
  methods: {
    createPeriod() {
      return this.create().then(() => {
        this.api.reload(this.camp)
      })
    },
  },
}
</script>

<style scoped></style>
