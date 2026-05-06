import { onMounted, onBeforeUnmount } from 'vue'
import $ from 'jquery'
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css'
import 'datatables.net-bs5'

const currentLocale = sessionStorage.getItem('local') ?? 'en'

const languageFiles = {
  ar: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json',
  nl: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Dutch.json',
  en: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/English.json',
  fr: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/French.json',
  it: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Italian.json',
  pt: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese.json',
  es: 'https://cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
}

const useDataTable = ({
  tableRef,
  columns,
  data = [],
  url = null,
  message = '',              // Default to empty string
  actionCallback,
  per_page = 10,
  advanceFilter = undefined,
  isTable = false,
  ordering = true,           // Enable/disable sorting globally
  dom = '<"row align-items-center"<"col-md-6" l><"col-md-6" f>><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">'
}) => {
  onMounted(async () => {
    setTimeout(async () => {
      let languageSettings = {}

      //   const languageUrl = languageFiles[currentLocale] || languageFiles['en'];
      //   const lat = localStorage.getItem('loction_current_lat');
      //   const long = localStorage.getItem('loction_current_long');

      //   let noDataMessage = 'No data available in the table';

      //   if (lat != '' && long != '') {
      //     noDataMessage = message ? message : 'Currently, there are no data available in this zone';
      //   }

      const languageUrl = languageFiles[currentLocale] || languageFiles['en'];
      const lat = localStorage.getItem('loction_current_lat');
      const long = localStorage.getItem('loction_current_long');

      let noDataMessage = 'No data available in the table';

      // Check if location data is available or not
      if (lat && long) {
        // Location data exists (location is on)
        noDataMessage = message ? message : 'Currently, there are no data available in this zone';
      } else {
        // Location data does not exist (location is off)
        noDataMessage = message ? message : 'No data available in the table';
      }


      try {
        const res = await fetch(languageUrl)
        languageSettings = await res.json()
        languageSettings.info = '_START_ to _END_ of _TOTAL_ entries'
        languageSettings.lengthMenu = 'Display _MENU_ entries' // Generic, dynamic part added below
        languageSettings.emptyTable = noDataMessage
      } catch (err) {
        languageSettings = {
          info: '_START_ to _END_ of _TOTAL_ entries',
          lengthMenu: 'Display _MENU_ entries'
        }
      }

      let datatableObj = {
        dom: dom,
        autoWidth: false,
        columns: columns,
        language: languageSettings,
        ordering: ordering,

        initComplete: function () {
          const api = this.api()
          let pageInfo
          try {
            pageInfo = api.page?.info?.()
          } catch (e) {
            pageInfo = null
          }
          const totalEntries = pageInfo?.recordsTotal ?? 0
          const end = pageInfo?.end ?? 0

          // Inject custom message next to length menu
          const lengthMenuContainer = $(this)
            .closest('.dataTables_wrapper')
            .find('.dataTables_length label')

          if (lengthMenuContainer.length) {
            lengthMenuContainer.append(
              `<span class="ms-2 text-muted custom-length-info">Showing 1 to ${end} of ${totalEntries} entries</span>`
            )
          }

          // Custom styling for tbody
          if (!isTable) {
            if (tableRef.value.id === 'helpdesk-datatable') {
              $(tableRef.value)
                .find('tbody')
                .addClass('row row-cols-xl-3 row-cols-lg-3 row-cols-sm-2')
            } else if (tableRef.value.classList?.contains('category-page-grid')) {
              $(tableRef.value)
                .find('tbody')
                .addClass('row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3')
            } else if (tableRef.value.classList?.contains('classified-posts-grid')) {
              // Classified listing uses CSS grid in post.blade.php (.classified-posts-wrap).
              // Do not add Bootstrap .row on tbody — its negative margins + .table-responsive overflow clip the first column.
            } else {
              $(tableRef.value)
                .find('tbody')
                .addClass('row row-cols-xl-4 row-cols-lg-3 row-cols-sm-2')
            }
          }
        },

        drawCallback: function () {
          try {
            let pageInfo = null
            const el = tableRef.value
            if (el && $.fn.DataTable.isDataTable(el)) {
              try {
                pageInfo = $(el).DataTable().page.info()
              } catch (e2) {
                /* ignore */
              }
            }

            const lengthInfoSpan = $(this)
              .closest('.dataTables_wrapper')
              .find('.dataTables_length .custom-length-info')

            if (
              lengthInfoSpan.length &&
              pageInfo &&
              typeof pageInfo.recordsTotal === 'number'
            ) {
              const start = pageInfo.start ?? 0
              const end = pageInfo.end ?? 0
              const total = pageInfo.recordsTotal
              lengthInfoSpan.text(`Showing ${start + 1} to ${end} of ${total} entries`)
            }

            if (el?.classList?.contains('category-page-grid')) {
              $(el).find('tbody').addClass('row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3')
            }
          } catch (e) {
            /* never break DataTables draw */
          }
        }
      }

      if (url) {
        datatableObj = {
          ...datatableObj,
          processing: true,
          serverSide: true,
          pageLength: per_page,
          ajax: {
            url: url,
            data: function (d) {
              if (typeof advanceFilter === 'function' && advanceFilter() !== undefined) {
                d.filter = { ...d.filter, ...advanceFilter() }
              }
            }
          }
        }
      }

      if (data.length) {
        datatableObj = {
          ...datatableObj,
          data: data
        }
      }

      let datatable = $(tableRef.value).DataTable(datatableObj)

      if (typeof actionCallback === 'function') {
        $(datatable.table().body()).on('click', '[data-table="action"]', function () {
          actionCallback({
            id: $(this).data('id'),
            method: $(this).data('method')
          })
        })
      }
    }, 0)
  })

  onBeforeUnmount(() => {
    if ($.fn.DataTable.isDataTable(tableRef.value)) {
      $(tableRef.value).DataTable().destroy()
    }
    $(tableRef.value).empty()
  })
}

export default useDataTable
