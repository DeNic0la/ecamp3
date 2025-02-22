<template>
  <div>
    <slot
      :schedule-entries="filteredScheduleEntries"
      :loading="loading"
      :reload-entries="reloadScheduleEntries"
      :on="eventHandlers"
    />
    <dialog-activity-create
      ref="dialogActivityCreate"
      :period="period"
      :schedule-entry="newScheduleEntry"
      @activity-created="afterCreateActivity($event)"
    />

    <v-btn
      v-if="showButton"
      :fixed="$vuetify.breakpoint.mdAndUp"
      :absolute="!$vuetify.breakpoint.mdAndUp"
      dark
      fab
      style="z-index: 3"
      bottom
      right
      class="fab--bottom_nav float-right"
      color="red"
      @click.stop="createNewActivity()"
    >
      <v-icon>mdi-plus</v-icon>
    </v-btn>
  </div>
</template>

<script>
import DialogActivityCreate from './DialogActivityCreate.vue'

export default {
  name: 'ScheduleEntries',
  components: {
    DialogActivityCreate,
  },
  props: {
    period: { type: Object, required: true },
    showButton: { type: Boolean, required: true },
    matchFn: { type: Function, required: false, default: () => true },
  },
  data() {
    return {
      eventHandlers: {
        newEntry: this.newEntryFromPicasso,
      },
      newScheduleEntry: {
        period: () => this.period,
        start: null,
        end: null,
      },
    }
  },
  computed: {
    scheduleEntries() {
      // TODO for SideBar, add filtering for the current day, now that the API supports it
      return this.period.scheduleEntries()
    },
    filteredScheduleEntries() {
      return this.scheduleEntries.items.map((item) => ({
        ...item,
        filterMatch: this.matchFn(item),
      }))
    },
    loading() {
      return (
        this.scheduleEntries._meta.loading ||
        this.period.camp().activities()._meta.loading ||
        this.period.camp().categories()._meta.loading
      )
    },
  },

  methods: {
    createNewActivity() {
      this.newScheduleEntry.start = this.$date
        .utc(this.period.start)
        .add(8, 'hour')
        .format()
      this.newScheduleEntry.end = this.$date
        .utc(this.period.start)
        .add(9, 'hour')
        .format()
      this.showActivityCreateDialog()
    },
    showActivityCreateDialog() {
      this.$refs.dialogActivityCreate.showDialog = true
    },
    afterCreateActivity() {
      this.api.reload(this.period.scheduleEntries())
    },

    // Event Handler on.newEntry: update position & open create dialog
    newEntryFromPicasso(start, end) {
      this.newScheduleEntry.start = start
      this.newScheduleEntry.end = end
      this.showActivityCreateDialog()
    },

    async reloadScheduleEntries() {
      await this.api.reload(this.scheduleEntries)
    },
  },
}
</script>

<style lang="scss">
.fab--bottom_nav {
  position: fixed;
  bottom: calc(16px + 56px + env(safe-area-inset-bottom)) !important;
  @media #{map-get($display-breakpoints, 'md-and-up')} {
    bottom: calc(16px + env(safe-area-inset-bottom)) !important;
  }
}
</style>
