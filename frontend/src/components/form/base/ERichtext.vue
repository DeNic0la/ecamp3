<template>
  <ValidationProvider
    v-slot="{ errors: veeErrors }"
    tag="div"
    :name="validationLabel"
    :vid="veeId"
    :rules="veeRules"
    :required="required"
    class="e-form-container"
  >
    <v-tiptap-editor
      v-bind="$attrs"
      :with-extensions="true"
      :filled="filled"
      :hide-details="hideDetails"
      :error-messages="veeErrors.concat(errorMessages)"
      :label="labelOrEntityFieldLabel"
      :class="[inputClass]"
      v-on="$listeners"
    >
      <!-- passing through all slots -->
      <slot v-for="(_, name) in $slots" :slot="name" :name="name" />
      <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>
    </v-tiptap-editor>
  </ValidationProvider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'
import { formComponentPropsMixin } from '@/mixins/formComponentPropsMixin.js'
import VTiptapEditor from '@/components/form/tiptap/VTiptapEditor.vue'
import { formComponentMixin } from '@/mixins/formComponentMixin.js'

export default {
  name: 'ERichtext',
  components: { VTiptapEditor, ValidationProvider },
  mixins: [formComponentPropsMixin, formComponentMixin],
}
</script>
