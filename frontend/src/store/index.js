import Vue from 'vue'
import Vuex from 'vuex'
import axios from 'axios'
import VueAxios from 'vue-axios'

Vue.use(Vuex)
axios.defaults.withCredentials = true
Vue.use(VueAxios, axios)

const API_ROOT = process.env.VUE_APP_ROOT_API

export default new Vuex.Store({
  state: {
    // TODO find a way to split the API state up and dynamically add independent state entries for each entity
    api: {}
  },

  mutations: {
    addEmpty (state, uri) {
      Vue.set(state.api, uri, { _loading: true })
    },
    add (state, dataArray) {
      dataArray.forEach(entry => {
        Vue.set(state.api, entry._links.self.href, entry)
      })
    }
  },

  strict: process.env.NODE_ENV !== 'production'
})

async function fetchFromAPI (store, uri) {
  store.commit('addEmpty', uri)
  let data = await Vue.axios.get(uri).data
  store.commit('add', replaceRelationsWithURIs(data))
  // TODO replace linked entities with store pointers
  // TODO save Proxy objects instead of raw data in order to automatically resolve store pointers when accessing properties
}

function replaceRelationsWithURIs (data) {
  let toAdd = []
  Object.keys(data).forEach(key => {
    if (data[key].hasOwnProperty('_links')) {
      // embedded single entity
      toAdd.concat(replaceRelationsWithURIs(data[key]))
      data[key] = data[key]._links.self.href
    } else if (Array.isArray(data[key])) {
      // embedded collection (not paginated, full list)
      data[key].forEach((entry, index) => {
        toAdd.concat(replaceRelationsWithURIs(entry))
        data[key][index] = data[key][index]._links.self.href
      })
    }
  })
  return toAdd
}

Vue.mixin({
  computed: {
    api: function ({ apiPath, start }) {
      if (!start) {
        start = API_ROOT
      } else if (typeof start === 'object') {
        start = start._links.self.href
      }
      if (!this.$store.state.api.hasOwnProperty(start)) {
        fetchFromAPI(start)
      }
      if (!apiPath || apiPath.length === 0) {
        return this.$store.state.api[start]
      }
      return this.api({ apiPath: apiPath.slice(1), start: this.$store.state.api[start][apiPath[0]] })
    }
  }
})
