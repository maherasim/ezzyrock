
@extends('landing-page.layouts.default')

@section('after_head')
<style>
.olx-post-card__media { aspect-ratio: 4/3; }
.olx-post-card__img { height: 100%; min-height: 140px; object-fit: cover; display: block; }
.olx-post-card__featured {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    background: #ffce32; color: #002f34; font-size: 10px; font-weight: 700;
    padding: 4px 8px; letter-spacing: 0.04em; border-radius: 2px;
}
.olx-post-card__fav {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    width: 36px; height: 36px; border-radius: 50%; background: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,.12); color: #002f34; cursor: default;
}
.olx-post-card__price { font-size: 1.125rem; color: #002f34; }
.classified-posts-wrap { background: #ebf0f1; }
/* Category strip: same width as .container content (no viewport breakout) */
.classified-cat-strip-outer {
    width: 100%;
    max-width: 100%;
    margin-left: 0;
    margin-right: 0;
    background: #fff;
    padding: 0.75rem 0;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid #eaefef;
    overflow-x: hidden;
    overflow-y: visible;
    box-sizing: border-box;
}
.classified-cat-strip-outer .classified-cat-bar--wrap {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    max-width: 100%;
}
.classified-cat-strip-outer .classified-cat-bar__all,
.classified-cat-strip-outer .classified-cat-bar__pill {
    flex: 0 0 auto !important;
    white-space: nowrap !important;
}
/* Classified post grid: pure CSS grid so we always get 6 cards per row on desktop.
   Scope to this page so it works even if JS build is stale (missing extra class). */
.classified-posts-wrap #datatable.custom-card-table {
    border-collapse: separate;
    border-spacing: 0;
}
.classified-posts-wrap #datatable.custom-card-table thead {
    display: none;
}
.classified-posts-wrap #datatable.custom-card-table tbody {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
    /* Cancel Bootstrap .row if ever present (negative margins + table-responsive = clipped first column) */
    margin-left: 0 !important;
    margin-right: 0 !important;
}
/* Let the grid breathe; default overflow-x:auto can still clip with nested wrappers */
.classified-posts-wrap .col-lg-9 .table-responsive,
.classified-posts-wrap .dataTables_wrapper .table-responsive {
    overflow-x: visible;
}
.classified-posts-wrap #datatable.custom-card-table tbody tr,
.classified-posts-wrap #datatable.custom-card-table tbody td {
    display: block !important;
    width: 100% !important;
    border: 0 !important;
    padding: 0 !important;
}
.classified-posts-wrap #datatable.custom-card-table tbody td > * {
    width: 100%;
}
@media (min-width: 576px) {
    .classified-posts-wrap #datatable.custom-card-table tbody {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (min-width: 992px) {
    .classified-posts-wrap #datatable.custom-card-table tbody {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }
}
</style>
@endsection

@section('content')
<div class="classified-posts-wrap section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <service-page
                    post-listing="true"
                    link="{{ route('post.data', ['id' => $id, 'type' => $type, 'latitude' => $latitude, 'longitude' => $longitude]) }}">
                </service-page>
            </div>
        </div>
    </div>
</div>
@endsection
