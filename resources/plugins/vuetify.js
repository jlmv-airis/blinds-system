
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import vuetifyColors from 'vuetify/lib/util/colors'
Vue.use(Vuetify)
// console.log(vuetifyColors)
const opts = {
   theme: {
      themes: {
         light: {
            ls_one: '#DDE6ED',
            ls_two: '#9DB2BF',
            ls_three: '#526D82',
            ls_four: '#27374D',
            ls_pdf: '#f40f02',
            ls_excel: '#1d6f42',
            ls_red: vuetifyColors.red.darken2, // D32F2F
            ls_green: vuetifyColors.lightGreen.darken2, // 689F38
            ls_purple: vuetifyColors.purple.accent3,
            ls_blue: vuetifyColors.blue.darken1,
            ls_yellow: vuetifyColors.yellow.darken2,
            ls_grey: vuetifyColors.blueGrey.darken1,
            ls_yt:'#c4302b'
         },
      },
   },
}

export default new Vuetify(opts)