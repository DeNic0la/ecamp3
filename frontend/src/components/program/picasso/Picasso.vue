<!--
Listing all given activity schedule entries in a calendar view.
-->

<template>
  <div>
    <v-calendar
      ref="calendar"
      v-model="value"
      v-resize="resize"
      :class="['e-picasso', editable && 'e-picasso--editable']"
      :events="events"
      event-start="startTimestamp"
      event-end="endTimestamp"
      event-color="transparent"
      :interval-height="computedIntervalHeight"
      interval-width="46"
      :interval-format="intervalFormat"
      :first-interval="firstInterval"
      :interval-count="intervalCount"
      :interval-minutes="intervalMinutes"
      :start="start"
      :end="end"
      :locale="$i18n.locale"
      :day-format="dayFormat"
      :type="type"
      :max-days="maxDays"
      :weekday-format="weekdayFormat"
      :weekdays="[1, 2, 3, 4, 5, 6, 0]"
      color="primary"
      :event-ripple="false"
      v-on="vCalendarListeners"
      @mouseleave.native="onMouseleave"
      @mousedown.native.prevent="
        /*this prevents from middle button to start scroll behavior*/
      "
    >
      <!-- day header -->
      <template #day-label-header="{ date }">
        <slot name="day-label-header" :date="date">
          <div class="e-picasso-daily_head-day-label">
            {{
              entryWidth > 140
                ? $date
                    .utc(date)
                    .format($tc('components.program.picasso.picasso.datetime.fullDate'))
                : $date
                    .utc(date)
                    .format(
                      $tc(
                        'components.program.picasso.picasso.datetime.smallDate',
                        widthPluralization
                      )
                    )
            }}
          </div>
          <day-responsibles :date="date" :period="period" :readonly="!editable" />
        </slot>
      </template>

      <!-- template for single scheduleEntry -->
      <template #event="{ event }">
        <PicassoEntry
          :schedule-entry="event"
          :editable="editable"
          @start-resize="startResize(event)"
          @finish-edit="reloadScheduleEntries"
        />
      </template>
    </v-calendar>

    <v-snackbar v-model="isSaving" light>
      <v-icon class="mdi-spin">mdi-loading</v-icon>
      {{ $tc('global.button.saving') }}
    </v-snackbar>
  </div>
</template>
<script>
import Vue, { reactive, ref, toRefs, watch, computed } from 'vue'
import { useDragAndDropMove } from './useDragAndDropMove.js'
import { useDragAndDropResize } from './useDragAndDropResize.js'
import { useDragAndDropNew } from './useDragAndDropNew.js'
import { useDragAndDropReminder } from './useDragAndDropReminder.js'
import { apiStore as api } from '@/plugins/store'
import mergeListeners from '@/helpers/mergeListeners.js'
import { timestampToUtcString, utcStringToTimestamp } from './dateHelperVCalendar.js'
import DayResponsibles from './DayResponsibles.vue'
import { ONE_DAY_IN_MILLISECONDS } from '@/helpers/vCalendarDragAndDrop.js'
import { errorToMultiLineToast } from '@/components/toast/toasts'
import PicassoEntry from './PicassoEntry.vue'

export default {
  name: 'Picasso',
  components: {
    PicassoEntry,
    DayResponsibles,
  },
  props: {
    // period for which to show picasso
    period: {
      type: Object,
      required: true,
    },

    // collection of scheduleEntries
    scheduleEntries: {
      type: Array,
      required: true,
    },

    // false disables drag & drop and disabled edit dialogs
    editable: {
      type: Boolean,
      required: false,
      default: false,
    },

    // v-calendar start: starting date (first day)
    start: {
      type: String,
      required: true,
    },

    // v-calender end: end date (last day)
    end: {
      type: String,
      required: true,
    },

    // v-calendar type
    type: {
      type: String,
      required: false,
      default: 'custom-daily',
    },

    // v-calendar intervalHeight
    intervalHeight: {
      type: Number,
      required: false,
      default: null,
    },

    isFilterSet: {
      type: Boolean,
      default: false,
    },

    reload: {
      type: Function,
      default: () => {},
    },
  },

  // emitted events
  emits: [
    'new-entry', // triggered once when a new entry was created via drag & drop (parameters: startTimestamp, endTimestamp)
    'unlock-reminder', // triggered when we think someone is trying to create/move in non-editable mode
  ],

  // composition API setup
  setup(props, { emit }) {
    const { editable, scheduleEntries, start, end, isFilterSet } = toRefs(props)

    const dragAndDropNewEnabled = computed(() => editable.value && !isFilterSet.value)

    const isSaving = ref(false)

    // callback used to save entry to API
    const updateEntry = (scheduleEntry, startTimestamp, endTimestamp) => {
      const patchData = {
        start: timestampToUtcString(startTimestamp),
        end: timestampToUtcString(endTimestamp),
      }
      isSaving.value = true
      api
        .patch(scheduleEntry._meta.self, patchData)
        .catch((error) => {
          Vue.$toast.error(errorToMultiLineToast(error))
        })
        .finally(() => {
          isSaving.value = false
          reloadScheduleEntries()
        })
    }

    // callback used to create new entry
    const placeholder = reactive({
      startTimestamp: 0,
      endTimestamp: 0,
      timed: true,
      tmpEvent: true,
    })
    const updatePlaceholder = (startTimestamp, endTimestamp) => {
      placeholder.startTimestamp = startTimestamp
      placeholder.endTimestamp = endTimestamp
    }
    const createEntry = (startTimestamp, endTimestamp) => {
      const start = timestampToUtcString(startTimestamp)
      const end = timestampToUtcString(endTimestamp)
      emit('new-entry', start, end)
    }

    const showReminder = (move) => {
      emit('unlock-reminder', move)
    }

    const calenderStartTimestamp = computed(() => utcStringToTimestamp(start.value))
    const calendarEndTimestamp = computed(
      () => utcStringToTimestamp(end.value) + ONE_DAY_IN_MILLISECONDS
    )

    const dragAndDropMove = useDragAndDropMove(
      editable,
      5,
      updateEntry,
      calenderStartTimestamp,
      calendarEndTimestamp
    )
    const dragAndDropResize = useDragAndDropResize(
      editable,
      updateEntry,
      calendarEndTimestamp
    )
    const dragAndDropNew = useDragAndDropNew(
      dragAndDropNewEnabled,
      updatePlaceholder,
      createEntry
    )
    const dragAndDropReminder = useDragAndDropReminder(editable, showReminder)

    // merge mouseleave handlers
    // this is needed, because .native modifiers doesn't work with v-on property
    // https://github.com/vuejs/vue/issues/5578#issuecomment-516932359
    const onMouseleave = () => {
      dragAndDropMove.nativeMouseLeave()
      dragAndDropResize.nativeMouseLeave()
      dragAndDropNew.nativeMouseLeave()
    }

    // merge v-calendar listeners
    const vCalendarListeners = mergeListeners([
      dragAndDropMove.vCalendarListeners,
      dragAndDropResize.vCalendarListeners,
      dragAndDropNew.vCalendarListeners,
      dragAndDropReminder.vCalendarListeners,
    ])

    // make events a reactive array + load event array from schedule entries
    const events = ref([])
    const loadCalenderEventsFromScheduleEntries = () => {
      // prepare scheduleEntries to make them understandable by v-calendar
      events.value = scheduleEntries.value.map((entry) => ({
        ...entry,
        startTimestamp: utcStringToTimestamp(entry.start),
        endTimestamp: utcStringToTimestamp(entry.end),
        timed: true,
        isResizing: false,
        isMoving: false,
      }))

      // add placeholder for drag & drop (create new entry)
      events.value.push(placeholder)
    }
    loadCalenderEventsFromScheduleEntries()

    // watch for changes from API
    watch(scheduleEntries, loadCalenderEventsFromScheduleEntries)

    // reloads schedule entries from API + recreates event array after reload
    const reloadScheduleEntries = async () => {
      await props.reload()
      loadCalenderEventsFromScheduleEntries()
    }

    return {
      vCalendarListeners,
      startResize: dragAndDropResize.startResize,
      onMouseleave,
      isSaving,
      reloadScheduleEntries,
      loadCalenderEventsFromScheduleEntries,
      events,
    }
  },
  data() {
    return {
      maxDays: 100,
      entryWidth: 80,
      value: '',
      activitiesLoading: true,
      categoriesLoading: true,

      // interval configuration for v-calendar
      // only 0-24 supported at the moment, until https://github.com/vuetifyjs/vuetify/issues/14603 is resolved
      intervalMinutes: 60,
      firstInterval: 0,
      intervalCount: 24,
    }
  },
  computed: {
    widthPluralization() {
      if (this.entryWidth < 86) {
        return 0
      } else if (this.entryWidth < 89) {
        return 1
      } else {
        return 2
      }
    },
    camp() {
      return this.period.camp()
    },
    computedIntervalHeight() {
      return this.intervalHeight ?? this.$vuetify.breakpoint.xsOnly
        ? (1.3 * (this.$vuetify.breakpoint.height - 140)) / this.intervalCount
        : 1.3 * Math.max((this.$vuetify.breakpoint.height - 204) / this.intervalCount, 32)
    },
  },
  mounted() {
    this.period
      .camp()
      .activities()
      ._meta.load.then(() => {
        this.activitiesLoading = false
      })
    this.period
      .camp()
      .categories()
      ._meta.load.then(() => {
        this.categoriesLoading = false
      })

    // scroll a bit down to hide the night hours
    const scroller = this.$el.querySelector('.v-calendar')
    scroller.scrollTo({ top: 250 })
  },
  methods: {
    resize() {
      const widthIntervals = 46
      this.entryWidth = Math.max(
        (this.$refs.calendar.$el.offsetWidth - widthIntervals) /
          this.$refs.calendar.days.length,
        85
      )
    },
    intervalFormat(time) {
      return this.$date
        .utc(time.date + ' ' + time.time)
        .format(this.$tc('global.datetime.hourLong'))
    },
    dayFormat(day) {
      if (this.$vuetify.breakpoint.smAndDown) {
        return this.$date.utc(day.date).format(this.$tc('global.datetime.dateShort'))
      } else {
        return this.$date.utc(day.date).format(this.$tc('global.datetime.dateLong'))
      }
    },
    weekdayFormat() {
      return ''
    },
  },
}
</script>

<style scoped lang="scss">
.e-picasso {
  border: none;
  overflow: auto;

  @media #{map-get($display-breakpoints, 'xs-only')} {
    position: fixed;
    height: inherit;
    top: 48px;
    bottom: calc(56px + env(safe-area-inset-bottom));
    left: 0;
    right: 0;
  }

  @media #{map-get($display-breakpoints, 'sm-and-up')} {
    height: calc(100vh - 106px);
  }

  @media #{map-get($display-breakpoints, 'md-and-up')} {
    height: calc(100vh - 136px - var(--schedule-entry-filters-height));
  }

  :deep {
    .v-event-timed-container {
      margin-right: 3px;
      @media #{map-get($display-breakpoints, 'sm-and-up')} {
        margin-right: 5px;
      }
      @media #{map-get($display-breakpoints, 'md-and-up')} {
        margin-right: 6px;
      }
    }

    .v-calendar-daily_head-day,
    .v-calendar-daily__day {
      min-width: 80px;
    }

    .v-event-timed {
      padding: 0;
      white-space: normal;
      border: none !important;

      &:has(.e-picasso-entry--filtered) {
        pointer-events: none;
      }

      &:has(.e-picasso-entry:not(.e-picasso-entry--filtered)) {
        z-index: 1;

        &:hover {
          z-index: 2;
        }
      }
    }

    .v-calendar-daily__day-container {
      width: initial;
    }

    .v-calendar-daily__head,
    .v-calendar-daily__intervals-body {
      position: sticky;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(4px);
    }

    .v-calendar-daily__head {
      top: 0;
      z-index: 2;
      min-width: fit-content;
      overflow: hidden;
      box-shadow:
        rgba(0, 0, 0, 0.1) 0 4px 6px -1px,
        rgba(0, 0, 0, 0.06) 0 2px 4px -1px;
    }

    .v-calendar-daily__pane,
    .v-calendar-daily__body {
      overflow: visible;
    }

    .v-calendar-daily__intervals-body {
      left: 0;
      z-index: 1;
    }

    .v-calendar-daily__scroll-area {
      overflow-y: visible;
    }
  }

  &:not(.e-picasso-entry--editable) {
    &:deep(.v-calendar-daily_head-day-label) {
      cursor: auto;

      .theme--light.v-text-field > .v-input__control > .v-input__slot {
        cursor: auto;

        &:before {
          border: none !important;
        }
      }
    }
  }
}

// day title
.e-picasso-daily_head-day-label {
  font-size: 11px;
  font-feature-settings: 'tnum';
  letter-spacing: -0.1px;
}

:deep(.v-calendar-daily_head-day-label) {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 0;

  .v-text-field .v-label {
    text-align: center;
    width: 100%;
    transform-origin: top center;
  }

  .e-form-container {
    display: contents;
  }

  .v-select {
    flex-direction: column;
  }

  .v-input__slot {
    align-items: stretch;
    height: 100%;
  }

  .v-select__slot {
    flex-wrap: wrap;
    align-content: space-between;
  }

  .v-select__selections {
    gap: 4px;
    padding: 2px;
    @media #{map-get($display-breakpoints, 'md-and-up')} {
      padding: 4px 2px;
    }
    width: 100%;
    min-width: initial;

    .v-chip {
      margin: 0;
      padding-left: 6px;
      padding-right: 6px;
    }

    > input {
      height: 1px;
      padding: 0;
      margin: 0;
    }
  }
}
</style>
