<style>
.category-list-six-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 1.25rem;
    width: 100%;
}
@media (max-width: 991.98px) {
    .category-list-six-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 575.98px) {
    .category-list-six-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
/* Landing: compact 8-column category strip (services / products / classifieds modules) */
.landing-category-compact-grid {
    display: grid;
    grid-template-columns: repeat(8, minmax(0, 1fr));
    gap: 0.5rem 0.4rem;
    width: 100%;
}
@media (max-width: 1199.98px) {
    .landing-category-compact-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }
}
@media (max-width: 991.98px) {
    .landing-category-compact-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
@media (max-width: 575.98px) {
    .landing-category-compact-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.4rem 0.3rem;
    }
}
.landing-category-compact-grid .landing-category-tile-img-bg {
    background: transparent !important;
    padding: 0.25rem !important;
    margin: 0 auto;
}
.landing-category-compact-grid .landing-category-tile-img {
    width: 52px;
    height: 52px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}
.landing-category-compact-grid .circle-clip-effect:hover .landing-category-tile-img-bg,
.landing-category-compact-grid .circle-clip-effect:hover .img-bg {
    background-color: transparent !important;
}
.landing-category-compact-grid .circle-clip-effect:hover .categories-name {
    color: inherit !important;
}
.landing-category-compact-grid .landing-category-tile-card:hover {
    background-color: transparent !important;
}
.category-list-six-item {
    min-width: 0;
}
.category-list-six-item > a {
    display: block;
    height: 100%;
    max-width: 100%;
}
.category-list-six-item .card {
    height: 100%;
}
.category-list-six-item .circle-clip-effect {
    overflow: hidden;
    max-width: 100%;
}
.category-list-six-item .category-card {
    overflow: hidden;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}
.category-list-six-item .category-card .img-bg {
    max-width: 100%;
    box-sizing: border-box;
    padding: 1rem 1rem !important;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    margin-right: auto;
}
.category-list-six-item .category-card .img-bg img {
    max-width: 100%;
    width: 70px;
    height: 70px;
    min-width: 0;
    object-fit: contain;
    vertical-align: middle;
}
.landing-category-compact-grid.category-list-six-item-wrap .category-card {
    padding-left: 0.25rem;
    padding-right: 0.25rem;
}
.category-list-six-item .categories-name {
    word-break: break-word;
    overflow-wrap: anywhere;
}
/* Landing products & posts: 6 + 6 grid (12 items) */
.landing-products-posts-grid .category-list-six-item .service-box-card {
    margin-bottom: 0;
    height: 100%;
}
.landing-products-posts-grid .category-list-six-item {
    display: flex;
}
.landing-products-posts-grid .category-list-six-item > .w-100 {
    min-width: 0;
    flex: 1;
}
/* Tighter gap between product categories and classified categories on landing */
.landing-category-modules-gap {
    margin-top: 1.25rem;
    padding-top: 0.75rem;
}
</style>
