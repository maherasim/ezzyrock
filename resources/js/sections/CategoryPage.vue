<template>

    <section ref="categorySection">
      <div class="row">
            <div class="col-md-12">
                <div class="float-end">
                    <div class="search-form input-group flex-nowrap align-items-center">
                        <input type="search" class="form-control rounded-3" name="search" v-model="search" placeholder="Search...">
                        <span v-if="search" class="input-group-text search-icon position-absolute text-body" @click="clearSearch" style="cursor: pointer;">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="6" y1="18" x2="18" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></line>
                                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></line>
                            </svg>
                        </span>
                        <span v-else class="input-group-text search-icon position-absolute text-body">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></circle><path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive rounded py-4 category-page-table-wrap">
            <table :id="tableId" ref="tableRef" class="table custom-card-table category-page-grid"></table>
        </div>

    </section>

</template>

<script setup>
import $ from 'jquery';
import { computed, ref, watch } from 'vue';
import CategoryCard from '../components/CategoryCard.vue';
import CategoryShimmer from '../shimmer/CategoryShimmer.vue';
import { useSection } from '../store/index';
import { useObserveSection } from '../hooks/Observer';
import useDataTable from '../hooks/Datatable'

const props = defineProps(['link']);
const tableId = computed(() => {
  try {
    const u = new URL(props.link || '', typeof window !== 'undefined' ? window.location.origin : 'http://localhost')
    const t = u.searchParams.get('type') || 'list'
    return 'category-datatable-' + t
  } catch {
    return 'category-datatable-list'
  }
})
const search = ref('')
watch(() => search.value, () => ajaxReload())
const columns = ref([
  { data: 'name', title: '', orderable: true,order: 'desc' }
]);
const tableRef = ref(null);
const ajaxReload = () => {
  if ($.fn.DataTable.isDataTable(tableRef.value)) {
    console.log('DataTable instance:', $(tableRef.value).DataTable());
    $(tableRef.value).DataTable().ajax.reload(null, false);
  } else {
    console.error('DataTable instance not found or not initialized yet.');
  }
};

useDataTable({
  tableRef: tableRef,
  columns: columns.value,
  url: props.link,
  per_page: 24,
  dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" l><"col-md-6 mt-md-0 mt-3" p>><"clear">',
  advanceFilter: () => {
    return {
        search: search.value,
    }
  }
});

// const store = useSection();
// const category_data = ref([]);
// const currentPage = ref(1);
// const itemsPerPage = 8;

// const totalPages = computed(() => {
//   const totalItems = store.categories_list.pagination?.total_items;
//   return totalItems ? Math.ceil(totalItems / itemsPerPage) : 0;
// });

// const [categorySection] = useObserveSection(async () => {
//   await getCategoryData(itemsPerPage,currentPage.value);
//   category_data.value = store.categries_list_data.data;
// });

// const getCategoryData = (itemsPerPage,currentPage) => {
//     return store.get_categries_list({
//       per_page: itemsPerPage,
//       page: currentPage,
//     });
// };

// const nextPage = async () => {
//   if (currentPage.value < totalPages.value) {
//     await getCategoryData(itemsPerPage,currentPage.value + 1);
//     currentPage.value += 1;
//     category_data.value = store.categries_list_data.data;
//   }
// };

// const prevPage = async () => {
//   if (currentPage.value > 1) {
//     await getCategoryData(itemsPerPage,currentPage.value - 1);
//     currentPage.value -= 1;
//     category_data.value = store.categries_list_data.data;
//   }
// };
// const gotoPage = async (page) => {
//   if (page >= 1 && page <= totalPages.value) {
//     await getCategoryData(itemsPerPage,page);
//     currentPage.value = page;
//     category_data.value = store.categries_list_data.data;
//   }
// };
// const isPageActive = (page) => {
//   return currentPage.value === page;
// };

const clearSearch = () =>{
  search.value = '';
}
</script>

<style>
/* Table layout breaks flex row on tbody; block lets Bootstrap row-cols work */
table.category-page-grid.custom-card-table {
    display: block !important;
    width: 100% !important;
}
/* Same layout as before (Bootstrap row on tbody + block tr/td), but 6 cols on xl */
.category-page-grid.custom-card-table thead {
    display: none;
}
.category-page-grid.custom-card-table tbody td,
.category-page-grid.custom-card-table tbody tr {
    border: 0 !important;
    display: block !important;
}
.category-page-grid.custom-card-table tbody td {
    padding-left: 0 !important;
    padding-right: 0 !important;
    width: 100%;
}
.category-page-grid.custom-card-table .card {
    height: 100%;
}
.category-page-grid.custom-card-table thead,
.category-page-grid.custom-card-table tbody,
.category-page-grid.custom-card-table tfoot,
.category-page-grid.custom-card-table tr,
.category-page-grid.custom-card-table td,
.category-page-grid.custom-card-table th {
    white-space: initial;
}
/* Force 6 items per row (lg+); 3 on sm; 2 on xs — matches Services / Ecommerce / Classified sections */
table.category-page-grid.custom-card-table tbody.row {
    display: flex !important;
    flex-wrap: wrap !important;
    width: 100%;
    margin-left: -0.5rem;
    margin-right: -0.5rem;
    row-gap: 1rem;
}
table.category-page-grid.custom-card-table tbody.row > tr {
    box-sizing: border-box !important;
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
    flex: 0 0 50% !important;
    max-width: 50% !important;
}
@media (min-width: 576px) {
    table.category-page-grid.custom-card-table tbody.row > tr {
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
    }
}
@media (min-width: 992px) {
    table.category-page-grid.custom-card-table tbody.row > tr {
        flex: 0 0 16.666667% !important;
        max-width: 16.666667% !important;
    }
}
</style>
