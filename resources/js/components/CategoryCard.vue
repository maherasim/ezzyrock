<template>
    <a :href="`${baseUrl}/category-details/${category_id}`">
        <div class="card text-center border-0 bg-transparent shadow-none circle-clip-effect rounded-3 landing-category-tile-card">
            <div class="card-body category-card py-2 px-1">
                <div class="img-bg d-inline-block rounded-3 landing-category-tile-img-bg">
                <img :src="image" alt="icon" class="img-fluid landing-category-tile-img">
            </div>
            <h5 class="categories-name text-capitalize mt-2 mb-0 small fw-semibold line-count-1">{{ title }}</h5>
        </div>
    </div>
</a>
</template>
<script setup>
defineProps({
    image: {type: String, default: ''},
    title : {type: String, default: ''},
    category_id: { type: Number, default: 0 },
})
const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

</script>
