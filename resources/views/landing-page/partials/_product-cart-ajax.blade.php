<script>
(function () {
    window.updateHeaderProductCartCount = function (count) {
        var el = document.getElementById('header-cart-qty-badge');
        if (!el) return;
        var n = Math.max(0, parseInt(count, 10) || 0);
        el.textContent = n > 99 ? '99+' : String(n);
        el.setAttribute('data-count', String(n));
        if (n > 0) {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    };

    function isGuestIntentForm(form) {
        var a = (form.getAttribute('action') || '');
        return a.indexOf('add-intent') !== -1 || a.indexOf('cart/add-intent') !== -1;
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;
        if (!form.matches('.product-listing-add-form, .product-listing-qty-form, .product-listing-remove-form')) return;
        if (isGuestIntentForm(form)) return;

        e.preventDefault();
        e.stopPropagation();

        var wrap = form.closest('.product-card-cart');
        var fd = new FormData(form);
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
        var token = document.querySelector('meta[name="csrf-token"]');
        if (token) headers['X-CSRF-TOKEN'] = token.getAttribute('content');

        fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: headers,
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (data) {
                return { res: res, data: data };
            }).catch(function () {
                return { res: res, data: {} };
            });
        }).then(function (o) {
            if (!o.res.ok) {
                var msg = (o.data && o.data.message) ? o.data.message : 'Could not update cart.';
                if (typeof window.Swal !== 'undefined') {
                    window.Swal.fire({ icon: 'error', text: msg });
                } else {
                    alert(msg);
                }
                return;
            }
            if (wrap && o.data && o.data.html) {
                wrap.innerHTML = o.data.html;
            }
            if (o.data && typeof o.data.cart_count === 'number') {
                window.updateHeaderProductCartCount(o.data.cart_count);
            }
        }).catch(function () {
            alert('Network error.');
        });
    }, true);
})();
</script>
