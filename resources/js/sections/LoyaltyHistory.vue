<template>
    <section>
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">History</h4>
            <div class="d-flex align-items-center gap-3 all-filter">
                <select ref="filterDropdownRef" id="filterDropdown" v-model="filterType" class="form-select select2 w-100">
                    <option value="">All</option>
                    <option value="last_week">Last Week</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_year">Last Year</option>
                </select>
            </div>
        </div>

        <div class="table-responsive rounded mb-3">
            <table id="datatable" ref="tableRef" class="table border rounded-3 table-custom-bg"></table>
        </div>
    </section>
</template>
<script setup>
import $ from 'jquery';
import { ref, watch, onMounted } from 'vue';
import useDataTable from '../hooks/Datatable'
import 'select2'

const props = defineProps(['link']);

const search = ref('')
const filterType = ref('')
const filterDropdownRef = ref(null)


$(filterDropdownRef.value).select2({
    width: '100%'
});

watch(() => search.value, () => ajaxReload())
watch(() => filterType.value, () => ajaxReload())

const tableRef = ref(null);

const ajaxReload = () => {
    $(tableRef.value).DataTable().ajax.reload(null, false);
};

onMounted(() => {
    $(filterDropdownRef.value).select2({
        width: '100%',
        minimumResultsForSearch: -1 // Hide search box for small lists
    });

    $(filterDropdownRef.value).on('change', function() {
        filterType.value = $(this).val();
    });
});

const columns = ref([
  { data: 'loyalty_type',name : 'loyalty_type', title: 'Loyalty Type', orderable: false },
  { data: 'date_and_time', name : 'date_and_time', title: 'Date And Time', orderable: false },
  { data: 'loyalty_points', name : 'loyalty_points', title: 'Loyalty Points', orderable: false },
  { data: 'status', name : 'status', title: 'Status', orderable: false }
]);

useDataTable({
  tableRef: tableRef,
  columns: columns.value,
  url: props.link,
  isTable:true,
  ordering: false,
  dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" l><"col-md-6 mt-md-0 mt-3" p>><"clear">',
  advanceFilter: () => {
    return {
        type: filterType.value
    }
  }
});

</script>
