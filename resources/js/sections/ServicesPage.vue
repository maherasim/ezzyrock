<template>
    <section ref="servicesection">

      <div class="row align-items-center">
          <div class="col-xl-6">
              <div class="row gx-3">
                  <div v-if="!isPostListing" class="col-md-4">
                    <select ref="categoryDropdownRef" id="categoryDropdown" v-model="selectedCategory" class="me-5 form-select select2" :disabled="isEmpty">
                        <option value="">{{$t('landingpage.all_categories')}}</option>
                        <option v-for="category in category_data" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>

                  </div>
                  <div v-if="isProductListing" class="col-md-4 mt-md-0 mt-3">
                    <select ref="subcategoryDropdownRef" id="subcategoryDropdown" v-model="selectedSubcategory" class="me-5 form-select select2" :disabled="isEmpty">
                        <option value="">{{$t('landingpage.subcatgory')}}</option>
                        <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                    </select>
                  </div>
                  <div v-if="!isPostListing" class="col-md-4 mt-md-0 mt-3">
                    <select ref="providerDropdownRef" id="providerDropdown" v-model="selectedProvider" class="me-5 form-select select2" :disabled="isEmpty">
                        <option value="">{{$t('landingpage.all_providers')}}</option>
                        <option v-for="providers in provider_data" :key="providers.id" :value="providers.id">{{ [providers.first_name, providers.last_name].filter(Boolean).join(' ') }}</option>
                    </select>
                  </div>
                  <div :class="isPostListing ? 'col-md-4 mt-md-0 mt-3' : 'col-md-4 mt-md-0 mt-3'">
                      <select ref="priceDropdownRef" id="priceDropdown" v-model="selectedPriceRange" class="me-5 form-select select2" :disabled="isEmpty">
                          <option value="">{{$t('landingpage.all_price')}}</option>
                          <option :value="price" v-for="price in priceRanges" :key="price">{{ CURRENCY_SYMBOL }} {{ price }}</option>
                      </select>
                  </div>
              </div>
          </div>
          <div class="col-xl-6 mt-xl-0 mt-3">
              <div class="row">
                  <div class="col-md-l2">
                      <div class="d-flex align-items-md-center flex-sm-row flex-column gap-3 justify-content-lg-end">
                          <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <h6 class="text-body flex-shrink-0">{{$t('landingpage.sort_by')}}:</h6>
                            <div class="flex-grow-1">
                              <select ref="sortOptionRef" v-model="selectedSortOption" class="form-select select2" :disabled="isEmpty">
                                <option value="">{{$t('landingpage.select')}}</option>
                                <option v-if="!isPostListing" value="best_selling">{{$t('landingpage.best_selling')}}</option>
                                <option v-if="!isPostListing" value="top_rated">{{$t('landingpage.top_rated')}}</option>
                                <option value="newest">{{$t('landingpage.newest')}}</option>
                              </select>
                            </div>
                          </div>
                          <div class="flex-shrink-0">
                            <div class="search-form input-group flex-nowrap align-items-center">
                              <input type="text" class="form-control rounded-3" v-model="search" placeholder="Search" :disabled="isEmpty">
                              <span v-if="search" class="input-group-text search-icon position-absolute text-body" @click="clearSearch" style="cursor: pointer;">
                                  <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                      <line x1="6" y1="18" x2="18" y2="6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></line>
                                      <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></line>
                                  </svg>
                              </span>
                              <span v-else class="input-group-text search-icon position-absolute text-body">
                                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                  <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                  </circle>
                                  <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                  </path>
                                </svg>
                              </span>
                            </div>
                          </div>
                          <button  v-if="checkDropdowns" class="btn btn-outline-primary" @click="refreshDropdowns" :disabled="isEmpty">{{$t('landingpage.reset')}}</button>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <div v-if="isPostListing" class="row py-4">
        <div class="col-lg-3 mb-3 mb-lg-0">
          <div class="classified-subcat-sidebar bg-white rounded-3 border p-3 h-100">
            <h6 class="mb-2">{{ $t('landingpage.all_categories') }}</h6>
            <button
              type="button"
              class="btn w-100 text-start mb-2 classified-subcat-pill"
              :class="{ 'classified-subcat-pill--active': !selectedCategory }"
              @click="clearClassifiedCategory"
            >
              {{ $t('landingpage.all_categories_menu') }}
            </button>
            <button
              v-for="cat in category_data"
              :key="`side-cat-${cat.id}`"
              type="button"
              class="btn w-100 text-start mb-2 classified-subcat-pill"
              :class="{ 'classified-subcat-pill--active': String(selectedCategory) === String(cat.id) }"
              @click="selectClassifiedCategory(cat.id)"
            >
              {{ cat.name }}
            </button>

            <hr class="my-3">
            <h6 class="mb-3">{{ $t('landingpage.subcatgory') }}</h6>
            <button
              type="button"
              class="btn w-100 text-start mb-2 classified-subcat-pill"
              :class="{ 'classified-subcat-pill--active': !selectedSubcategory }"
              @click="clearSubcategory"
            >
              {{ $t('landingpage.all') }}
            </button>
            <template v-if="subcategories.length">
              <button
                v-for="sub in subcategories"
                :key="sub.id"
                type="button"
                class="btn w-100 text-start mb-2 classified-subcat-pill"
                :class="{ 'classified-subcat-pill--active': String(selectedSubcategory) === String(sub.id) }"
                @click="selectSubcategory(sub.id)"
              >
                {{ sub.name }}
              </button>
            </template>
            <p v-else class="small text-muted mb-0">{{ $t('messages.nodata') }}</p>
          </div>
        </div>
        <div class="col-lg-9">
          <div class="table-responsive rounded">
            <table id="datatable" ref="tableRef" class="table custom-card-table service-card-table" :class="{ 'classified-posts-grid': isPostListing }"></table>
          </div>
        </div>
      </div>
      <div v-else class="table-responsive rounded py-4">
        <table id="datatable" ref="tableRef" class="table custom-card-table service-card-table" :class="{ 'classified-posts-grid': isPostListing }"></table>
      </div>

    </section>
  </template>

  <style scoped>
  .classified-subcat-sidebar { position: sticky; top: 100px; }
  .classified-subcat-pill {
    background: #fff;
    border: 1px solid #d8dfe0;
    color: #002f34;
    font-size: 0.875rem;
  }
  .classified-subcat-pill:hover { border-color: #3a77ff; color: #3a77ff; }
  .classified-subcat-pill--active {
    background: #ebf2ff !important;
    border-color: #3a77ff !important;
    color: #3a77ff !important;
  }
  </style>

  <script setup>
  import $ from 'jquery';
  import axios from 'axios';
  import ServiceCard from '../components/ServiceCard.vue';
  import ServiceShimmer from '../shimmer/ServiceShimmer.vue';
  import { computed, onMounted, ref, watch } from 'vue';
  import { useSection } from '../store/index';
  import { useObserveSection } from '../hooks/Observer';
  import useDataTable from '../hooks/Datatable'
  import { BASE_URL } from '../data/api';

  const CURRENCY_SYMBOL = ref(window.defaultCurrencySymbol)

  const categoryDropdownRef = ref(null);
  const subcategoryDropdownRef = ref(null);
  const providerDropdownRef = ref(null);
  const priceDropdownRef = ref(null);
  const sortOptionRef = ref(null);
  const props = defineProps({
    link: String,
    isEmpty: Boolean,
    service: String,
    postListing: { type: [Boolean, String], default: false },
  });
  const isPostListing = computed(() => props.postListing === true || props.postListing === 'true');
  const isProductListing = computed(() => String(props.link || '').includes('product-datatable'));
  const selectClassifiedCategory = (id) => {
    if (String(selectedCategory.value) === String(id)) {
      ajaxReload();
      return;
    }
    selectedCategory.value = String(id);
  };
  const clearClassifiedCategory = () => {
    selectedSubcategory.value = '';
    if (!selectedCategory.value) {
      fetchSubcategories('');
      ajaxReload();
      return;
    }
    selectedCategory.value = '';
  };

  const isEmpty = props.isEmpty;

  const selectedCategory = ref('')
  watch(() => selectedCategory.value, () => ajaxReload())
  const subcategories = ref([]);
  const selectedSubcategory = ref('');
  watch(() => selectedSubcategory.value, () => ajaxReload())
  watch(() => selectedCategory.value, async (val) => {
    selectedSubcategory.value = '';
    if (!isPostListing.value && !isProductListing.value) return;
    await fetchSubcategories(val, isPostListing.value ? 'classified' : 'ecommerce');
  });
  const fetchSubcategories = async (categoryId = '', moduleType = 'classified') => {
    try {
      const params = new URLSearchParams({ module_type: moduleType, per_page: 'all' });
      if (categoryId) params.append('category_id', categoryId);
      const response = await axios.get(`${BASE_URL}/subcategory-list?${params.toString()}`);
      subcategories.value = response?.data?.data || [];
    } catch (e) {
      subcategories.value = [];
    }
  };

  const selectedProvider = ref('')
  watch(() => selectedProvider.value, () => ajaxReload())

  const selectedPriceRange = ref('')
  watch(() => selectedPriceRange.value, () => ajaxReload())

  const selectedSortOption = ref('')
  watch(() => selectedSortOption.value, () => ajaxReload())

  const search = ref('')
  watch(() => search.value, () => ajaxReload())

  const columns = ref([
    { data: 'name', title: '', orderable: false, order: 'desc'}
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
    per_page: isPostListing.value ? 24 : 10,
    dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" l><"col-md-6 mt-md-0 mt-3" p>><"clear">',
    advanceFilter: () => {
    return {
        selectedCategory: selectedCategory.value,
        selectedSubcategory: selectedSubcategory.value,
        selectedProvider: selectedProvider.value,
        selectedPriceRange: selectedPriceRange.value,
        selectedSortOption: selectedSortOption.value,
        search: search.value,
    }
  }
  });

  const store = useSection();
  const service_data = computed(() => store.service_list_data);
  const featured_category_data = computed(() => store.featured_category_list_data);
  const category_data = computed(() => {
    const raw = store.categries_list_data?.data;
    return Array.isArray(raw) ? raw : [];
  });
  const provider_data = computed(() => store.provider_list_data);

  const minPrice = computed(() => Math.min(...service_data.value.map(item => item.price)));
  const maxPrice = computed(() => Math.max(...service_data.value.map(item => item.price)));

  const postPriceBuckets = ['0-10000', '10000-25000', '25000-50000', '50000-100000', '100000-999999999'];
  const productPriceBuckets = ['0-1000', '1000-5000', '5000-10000', '10000-50000', '50000-999999999'];
  const priceRanges = computed(() => {
  if (isPostListing.value) {
    return postPriceBuckets;
  }
  if (isProductListing.value) {
    return productPriceBuckets;
  }
  if (!service_data.value.length || !Number.isFinite(minPrice.value) || !Number.isFinite(maxPrice.value)) {
    return [];
  }
  const range = maxPrice.value - minPrice.value;
  const step = Math.max(10, Math.ceil(range / 8));
  const count = Math.max(1, Math.ceil(range / step));

  const ranges = Array.from({ length: count + 1 }, (_, index) => ({
    min: minPrice.value + index * step,
    max: minPrice.value + (index + 1) * step,
  }));

  return ranges.map(range => `${range.min}-${range.max}`);
});

  const loadServiceData = () => {
      store.get_service_list({ per_page: 'all' });
    };

  const loadCategoryData = () =>{
    store.get_categries_list({ per_page: 'all' });
  }

  const loadProviderData = () =>{
    store.get_provider_list({ per_page: 'all', user_type: 'provider' });
  }

  const loadFeaturedCategoryData = () => {
    store.get_featured_category_list({ is_featured: 1 });
  };


  onMounted(async () => {
    if (isPostListing.value) {
      await store.get_categries_list({ per_page: 'all', module_type: 'classified' });
      const qs = new URLSearchParams((props.link || '').split('?')[1] || '');
      const type = qs.get('type');
      const id = qs.get('id');
      if (type === 'category-post' && id) {
        selectedCategory.value = String(id);
      } else if (type === 'subcategory-post' && id) {
        selectedSubcategory.value = String(id);
      }
      await fetchSubcategories(type === 'category-post' && id ? String(id) : '', 'classified');
    } else if (isProductListing.value) {
      await store.get_categries_list({ per_page: 'all', module_type: 'ecommerce' });
      const qs = new URLSearchParams((props.link || '').split('?')[1] || '');
      const type = qs.get('type');
      const id = qs.get('id');
      if (type === 'category-product' && id) {
        selectedCategory.value = String(id);
      } else if (type === 'subcategory-product' && id) {
        selectedSubcategory.value = String(id);
      }
      await fetchSubcategories(type === 'category-product' && id ? String(id) : '', 'ecommerce');
    }
    if (categoryDropdownRef.value) {
      $(categoryDropdownRef.value).select2({ width: '100%' });
      $(categoryDropdownRef.value).on('change', function() {
        selectedCategory.value = $(this).val();
      });
    }
    if (subcategoryDropdownRef.value) {
      $(subcategoryDropdownRef.value).select2({ width: '100%' });
      $(subcategoryDropdownRef.value).on('change', function() {
        selectedSubcategory.value = $(this).val();
      });
    }
    if (providerDropdownRef.value) {
      $(providerDropdownRef.value).select2({ width: '100%' });
      $(providerDropdownRef.value).on('change', function() {
        selectedProvider.value = $(this).val();
      });
    }
    $(priceDropdownRef.value).select2({ width: '100%' });
    $(sortOptionRef.value).select2({ width: '100%' });
    $(priceDropdownRef.value).on('change', function() {
      selectedPriceRange.value = $(this).val();
    });
    $(sortOptionRef.value).on('change', function() {
      selectedSortOption.value = $(this).val();
    });
    if (!isPostListing.value && !isProductListing.value) {
      loadServiceData();
      loadCategoryData();
      loadProviderData();
      loadFeaturedCategoryData();
    } else if (isProductListing.value) {
      loadProviderData();
    }

    if (!window.__serviceBoxCardNavBound) {
      window.__serviceBoxCardNavBound = true;
      $(document).on('click', '.service-box-card .service-heading[href], .service-box-card .service-img[href]', function (e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href) window.location.href = href;
      });
    }

    // Ecommerce listing: +/- updates cart (remove when decreasing from 1).
    if (!window.__productListingQtyBound) {
      window.__productListingQtyBound = true;
      document.addEventListener('click', function (e) {
        const btn = e.target && e.target.closest ? e.target.closest('.product-listing-qty-btn') : null;
        if (!btn) return;
        e.preventDefault();
        const wrap = btn.closest('.product-card-cart__active');
        const form = wrap ? wrap.querySelector('.product-listing-qty-form') : null;
        const input = form ? form.querySelector('.product-listing-qty-input') : null;
        const removeForm = wrap ? wrap.querySelector('.product-listing-remove-form') : null;
        if (!form || !input || !removeForm) return;

        const current = Number(input.value || 1);
        const max = Number(input.getAttribute('max') || 99);
        const action = btn.getAttribute('data-action');
        if (action === 'plus') {
          const next = Math.min(max, current + 1);
          input.value = String(next);
          if (typeof form.requestSubmit === 'function') form.requestSubmit();
          else form.submit();
          return;
        }
        if (current <= 1) {
          if (window.confirm(typeof window.__cartRemoveConfirm === 'string' ? window.__cartRemoveConfirm : 'Remove this item from cart?')) {
            if (typeof removeForm.requestSubmit === 'function') removeForm.requestSubmit();
            else removeForm.submit();
          }
          return;
        }
        input.value = String(current - 1);
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
      });
    }
  });

  const refreshDropdowns = () => {
    if (isPostListing.value) {
      selectedCategory.value = '';
      selectedSubcategory.value = '';
      fetchSubcategories('', 'classified');
    } else if (isProductListing.value) {
      if (categoryDropdownRef.value) {
        $(categoryDropdownRef.value).val('').trigger('change');
      }
      if (subcategoryDropdownRef.value) {
        $(subcategoryDropdownRef.value).val('').trigger('change');
      }
      selectedCategory.value = '';
      selectedSubcategory.value = '';
      fetchSubcategories('', 'ecommerce');
    } else if (categoryDropdownRef.value) {
      $(categoryDropdownRef.value).val('').trigger('change');
    }
    if (providerDropdownRef.value) {
      $(providerDropdownRef.value).val('').trigger('change');
    }
    $(priceDropdownRef.value).val('').trigger('change');
    $(sortOptionRef.value).val('').trigger('change');
    selectedPriceRange.value = '';
    selectedSortOption.value = '';
    if (!isPostListing.value) {
      selectedProvider.value = '';
    }
  }

  const checkDropdowns = computed(() => {
    return selectedCategory.value || selectedSubcategory.value || selectedProvider.value || selectedPriceRange.value || selectedSortOption.value
  });

  const clearSearch = () =>{
    search.value = '';
  }

  const selectSubcategory = (id) => {
    if (String(selectedSubcategory.value) === String(id)) {
      ajaxReload();
      return;
    }
    selectedSubcategory.value = String(id);
  };
  const clearSubcategory = () => {
    if (!selectedSubcategory.value) {
      ajaxReload();
      return;
    }
    selectedSubcategory.value = '';
  };

  </script>
