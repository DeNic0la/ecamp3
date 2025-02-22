<!--
Displays a field as a picker (can be used with v-model)
-->

<template>
  <div class="e-form-container e-picker-base">
    <v-menu
      v-model="showPicker"
      :disabled="disabled || readonly"
      :close-on-content-click="false"
      transition="scale-transition"
      offset-y
      offset-overflow
      min-width="290px"
      max-width="290px"
    >
      <template #activator="{ on }">
        <e-text-field
          :id="id"
          ref="textField"
          :value="fieldValue"
          :readonly="readonly"
          :disabled="disabled"
          :filled="filled"
          :hide-details="hideDetails"
          :input-class="inputClass"
          :path="path"
          :label="label"
          :validation-label-override="validationLabelOverride"
          v-bind="$attrs"
          :error-messages="combinedErrorMessages"
          @click="(...args) => (openOnTextFieldClick ? onMenuOpen(on, ...args) : null)"
          @input="debouncedParseValue"
        >
          <template #prepend>
            <slot
              name="prepend"
              :color="iconColor"
              :attrs="{
                'aria-label': $tc(buttonAriaLabelI18nKey, 0, {
                  label: labelOrEntityFieldLabel,
                }),
              }"
              :on="{ click: (...args) => onMenuOpen(on, ...args) }"
            >
              <v-icon
                :color="iconColor"
                :aria-label="
                  $tc(buttonAriaLabelI18nKey, 0, { label: labelOrEntityFieldLabel })
                "
                @click="(...args) => onMenuOpen(on, ...args)"
              >
                {{ icon }}
              </v-icon>
            </slot>
          </template>

          <!-- passing the append slot through -->
          <template #append>
            <slot name="append" />
          </template>
        </e-text-field>
      </template>
      <div :id="menuId">
        <slot :value="pickerValue" :on-input="inputFromPicker" :close="closePicker" />
      </div>
    </v-menu>
  </div>
</template>

<script>
import { debounce } from 'lodash'
import { formComponentPropsMixin } from '@/mixins/formComponentPropsMixin.js'

export default {
  name: 'BasePicker',
  inheritAttr: false,
  mixins: [formComponentPropsMixin],
  props: {
    value: { type: [Number, String], required: true },
    icon: { type: String, required: false, default: null },
    iconColor: { type: String, required: false, default: null },
    readonly: { type: Boolean, required: false, default: false },
    disabled: { type: Boolean, required: false, default: false },
    errorMessages: { type: Array, required: false, default: () => [] },
    openOnTextFieldClick: { type: Boolean, required: false, default: false },
    closeOnPickerInput: { type: Boolean, required: false, default: false },
    buttonAriaLabelI18nKey: { type: String, required: true },

    /**
     * Format internal value for display in the UI
     */
    format: { type: Function, required: false, default: null },

    /**
     * Format internal value for the popup component. If omitted, uses format instead.
     */
    formatPicker: { type: Function, required: false, default: null },

    /**
     * Parse a user-supplied value into the internal format
     */
    parse: { type: Function, required: false, default: null },

    /**
     * Parse the value from the popup component into the internal format. If omitted, uses parse instead.
     */
    parsePicker: { type: Function, required: false, default: null },
  },
  data() {
    return {
      // internal random string used for identifying the menu in the DOM
      random: Math.random().toString(36).substring(2),

      // internal value
      localValue: null,
      localValueInitialized: false,

      showPicker: false,
      parseError: null,
      // note that it is necessary to debounce in data to have one debounced function per instance, whereas
      // debouncing in watch or methods results in one global debounced function which has unwanted effects
      // when there are multiple picker instances rendered at the same time
      debouncedParseValue: debounce(this.parseValue, 500),
      clickOutsideHandler: null,
      escapeKeyHandler: null,
    }
  },
  computed: {
    // value formatted for text field
    fieldValue() {
      if (this.format !== null) {
        return this.format(this.localValue)
      } else {
        return this.localValue
      }
    },

    // value formatted for picker component
    pickerValue() {
      if (this.formatPicker !== null) {
        return this.formatPicker(this.localValue)
      } else if (this.format !== null) {
        return this.format(this.localValue)
      } else {
        return this.localValue
      }
    },
    combinedErrorMessages() {
      if (this.parseError == null) {
        return this.errorMessages
      }
      return [...this.errorMessages, this.parseError.message]
    },
    menuId() {
      return 'picker-menu-' + this.random
    },
  },
  watch: {
    value(val) {
      this.localValueInitialized = false
      this.setValue(val)
    },
  },
  mounted() {
    this.escapeKeyHandler = (event) => {
      if (event.code === 'Escape') {
        this.closePicker()
      }
    }
    document.addEventListener('keydown', this.escapeKeyHandler)

    this.setValue(this.value)
  },
  beforeDestroy() {
    if (this.clickOutsideHandler) {
      document.removeEventListener('click', this.clickOutsideHandler)
    }
    if (this.escapeKeyHandler) {
      document.removeEventListener('keydown', this.escapeKeyHandler)
    }
  },
  methods: {
    onMenuOpen(on, ...args) {
      if (typeof on.click === 'function') {
        return on.click(...args)
      }
      return () => {}
    },
    setValue(val) {
      if (this.localValue !== val) {
        this.localValue = val

        if (this.localValueInitialized) {
          this.$emit('input', val)
          // after saving value, trigger validations
          this.$refs.textField.$refs.validationProvider.validate(this.fieldValue)
        }
      }
      this.localValueInitialized = true
      this.setParseError(null)
    },
    async parseValue(val) {
      try {
        if (this.parse != null) {
          val = await this.parse(val)
        }
        this.setValue(val)
      } catch (error) {
        this.setParseError(error)
      }
    },
    setParseError(err) {
      this.parseError = err
    },
    closePicker() {
      this.showPicker = false
    },
    async inputFromPicker(val) {
      if (this.value === val) {
        return
      }
      try {
        if (this.parsePicker !== null) {
          val = await this.parsePicker(val)
        } else if (this.parse !== null) {
          val = await this.parse(val)
        }
        this.setValue(val)
        if (this.closeOnPickerInput) {
          this.closePicker()
        }
      } catch (error) {
        this.setParseError(error)
      }
    },
  },
}
</script>
