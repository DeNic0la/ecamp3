<template>
  <generic-error-message v-if="error" :error="error" />
  <div v-else class="tw-mb-20 tw-mt-1">
    <div
      :id="`scheduleEntry_${scheduleEntry.id}`"
      class="schedule-entry-title tw-break-after-avoid tw-flex tw-items-baseline tw-justify-between tw-gap-2 tw-border-b-4 tw-border-b-black tw-pb-2"
      :style="{ borderColor: scheduleEntry.activity().category().color }"
    >
      <h2
        :id="`content_${index}_scheduleEntry_${scheduleEntry.id}`"
        class="tw-text-3xl tw-font-semibold tw-pt-1 flex gap-3"
      >
        <category-label :category="scheduleEntry.activity().category()" />
        <span class="tw-tabular-nums">
          {{ scheduleEntry.number }}
        </span>
        <span>
          {{ scheduleEntry.activity().title }}
        </span>
      </h2>
      <div class="tw-text-lg">
        {{ rangeShort(scheduleEntry.start, scheduleEntry.end) }}
      </div>
    </div>
    <div class="header tw-break-inside-avoid tw-break-after-avoid tw-mb-4">
      <table class="header-table">
        <tr>
          <th class="header-row left-col">
            {{ $t('entity.activity.fields.location') }}
          </th>
          <td class="header-row">
            {{ scheduleEntry.activity().location }}
          </td>
        </tr>
        <tr>
          <th class="header-row left-col">
            {{ $t('entity.activity.fields.responsible') }}
          </th>
          <td class="header-row">
            {{ activityResponsibles }}
            <!-- <user-avatar
              v-for="responsible in responsibles"
              :key="responsible.id"
              :user="responsible.campCollaboration().user()"
              :size="16"
            /> -->
          </td>
        </tr>
      </table>
    </div>
    <content-node :content-node="scheduleEntry.activity().rootContentNode()" />
  </div>
</template>

<script setup>
const props = defineProps({
  scheduleEntry: { type: Object, required: true },
  index: { type: Number, required: true },
})

const { error } = await useAsyncData(
  `ScheduleEntry-${props.scheduleEntry._meta.self}`,
  async () => {
    await Promise.all([
      props.scheduleEntry._meta.load,
      props.scheduleEntry.activity()._meta.load,
      props.scheduleEntry.activity().rootContentNode()._meta.load,
      props.scheduleEntry.activity().category()._meta.load,
      props.scheduleEntry.period().camp().materialLists().$loadItems(),
      // prettier-ignore
      props.scheduleEntry.activity().activityResponsibles().$loadItems().then(
        (activityResponsibles) => {
          return Promise.all(activityResponsibles.items.map((activityResponsible) => {
            if ( activityResponsible.campCollaboration().user === null) {
              return Promise.resolve(null)
            }

            return activityResponsible.campCollaboration().user()._meta.load
          }
          ))
        }
      ),
    ])
  }
)
</script>

<script>
import CategoryLabel from '../generic/CategoryLabel.vue'
import ContentNode from './contentNode/ContentNode.vue'
import { dateHelperUTCFormatted } from '@/mixins/dateHelperUTCFormatted.js'
import { activityResponsiblesCommaSeparated } from '@/common/helpers/activityResponsibles.js'

export default defineNuxtComponent({
  components: { CategoryLabel, ContentNode },
  mixins: [dateHelperUTCFormatted],
  computed: {
    // responsibles() {
    //   return this.scheduleEntry.activity().activityResponsibles().items
    // },
    activityResponsibles() {
      return activityResponsiblesCommaSeparated(
        this.scheduleEntry.activity(),
        this.$t.bind(this)
      )
    },
  },
})
</script>

<style lang="scss" scoped>
.header-table {
  border-spacing: 0;
  width: 100%;
}

.header-row {
  border-bottom: 1px solid black;
  padding: 0.2rem;
  padding-left: 0.4rem;
  width: 90%;
}

.left-col {
  border-right: 1px solid #ddd;
  font-weight: 600;
  text-align: left;
  width: 10%;
  padding-left: 0;
  padding-right: 0.4rem;
}
</style>
