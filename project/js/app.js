function openAddProductForm() {
    var modalLabel = document.getElementById('productModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-box-seam me-2"></i>Add Product';
    var formMethod = document.getElementById('form-method');
    if (formMethod) formMethod.value = 'POST';
    var formId = document.getElementById('form-product-id');
    if (formId) formId.value = '';
    var name = document.getElementById('form-name');
    if (name) name.value = '';
    var sku = document.getElementById('form-sku');
    if (sku) sku.value = '';
    var unit = document.getElementById('form-unit');
    if (unit) unit.value = 'pcs';
    var category = document.getElementById('form-category');
    if (category) category.value = '0';
    var supplier = document.getElementById('form-supplier');
    if (supplier) supplier.value = '0';
    var cost = document.getElementById('form-cost');
    if (cost) cost.value = '0';
    var sell = document.getElementById('form-sell');
    if (sell) sell.value = '0';
    var qty = document.getElementById('form-qty');
    if (qty) qty.value = '0';
    var minQty = document.getElementById('form-minqty');
    if (minQty) minQty.value = '5';
    var qtyWrap = document.getElementById('qty-field-wrapper');
    if (qtyWrap) qtyWrap.style.display = '';
    var errorDiv = document.getElementById('modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');
}

function openEditProductForm(id, name, skuVal, categoryId, supplierId, unitVal, costPrice, sellPrice, minQty) {
    var modalLabel = document.getElementById('productModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Product';
    var formMethod = document.getElementById('form-method');
    if (formMethod) formMethod.value = 'PUT';
    var formId = document.getElementById('form-product-id');
    if (formId) formId.value = id;
    var nameEl = document.getElementById('form-name');
    if (nameEl) nameEl.value = name;
    var skuEl = document.getElementById('form-sku');
    if (skuEl) skuEl.value = skuVal || '';
    var unitEl = document.getElementById('form-unit');
    if (unitEl) unitEl.value = unitVal || 'pcs';
    var category = document.getElementById('form-category');
    if (category) category.value = categoryId;
    var supplier = document.getElementById('form-supplier');
    if (supplier) supplier.value = supplierId;
    var cost = document.getElementById('form-cost');
    if (cost) cost.value = costPrice;
    var sell = document.getElementById('form-sell');
    if (sell) sell.value = sellPrice;
    var minQtyEl = document.getElementById('form-minqty');
    if (minQtyEl) minQtyEl.value = minQty;
    var qtyWrap = document.getElementById('qty-field-wrapper');
    if (qtyWrap) qtyWrap.style.display = 'none';
    var errorDiv = document.getElementById('modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');

    var modalEl = document.getElementById('productModal');
    if (modalEl) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openAddCategoryForm() {
    var modalLabel = document.getElementById('categoryModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-tags me-2"></i>Add Category';
    var method = document.getElementById('cat-form-method');
    if (method) method.value = 'POST';
    var id = document.getElementById('cat-form-id');
    if (id) id.value = '';
    var name = document.getElementById('cat-name');
    if (name) name.value = '';
    var errorDiv = document.getElementById('cat-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');
}

function openEditCategoryForm(id, name) {
    var modalLabel = document.getElementById('categoryModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Category';
    var method = document.getElementById('cat-form-method');
    if (method) method.value = 'PUT';
    var catId = document.getElementById('cat-form-id');
    if (catId) catId.value = id;
    var nameEl = document.getElementById('cat-name');
    if (nameEl) nameEl.value = name;
    var errorDiv = document.getElementById('cat-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');

    var modalEl = document.getElementById('categoryModal');
    if (modalEl) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openAddSupplierForm() {
    var modalLabel = document.getElementById('supplierModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-truck me-2"></i>Add Supplier';
    var method = document.getElementById('sup-form-method');
    if (method) method.value = 'POST';
    var id = document.getElementById('sup-form-id');
    if (id) id.value = '';
    var name = document.getElementById('sup-name');
    if (name) name.value = '';
    var phone = document.getElementById('sup-phone');
    if (phone) phone.value = '';
    var email = document.getElementById('sup-email');
    if (email) email.value = '';
    var address = document.getElementById('sup-address');
    if (address) address.value = '';
    var errorDiv = document.getElementById('sup-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');
}

function openEditSupplierForm(id, name, phone, email, address) {
    var modalLabel = document.getElementById('supplierModalLabel');
    if (modalLabel) modalLabel.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Supplier';
    var method = document.getElementById('sup-form-method');
    if (method) method.value = 'PUT';
    var supId = document.getElementById('sup-form-id');
    if (supId) supId.value = id;
    var nameEl = document.getElementById('sup-name');
    if (nameEl) nameEl.value = name;
    var phoneEl = document.getElementById('sup-phone');
    if (phoneEl) phoneEl.value = phone;
    var emailEl = document.getElementById('sup-email');
    if (emailEl) emailEl.value = email;
    var addressEl = document.getElementById('sup-address');
    if (addressEl) addressEl.value = address;
    var errorDiv = document.getElementById('sup-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');

    var modalEl = document.getElementById('supplierModal');
    if (modalEl) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openAddUserForm() {
    var label = document.getElementById('userModalLabel');
    if (label) label.innerHTML = '<i class="bi bi-person-plus me-2"></i>Add New User';
    var method = document.getElementById('user-method');
    if (method) method.value = 'POST';
    var id = document.getElementById('user-id');
    if (id) id.value = '';
    var name = document.getElementById('user-fullname');
    if (name) name.value = '';
    var email = document.getElementById('user-email');
    if (email) email.value = '';
    var role = document.getElementById('user-role');
    if (role) role.value = 'staff';
    var status = document.getElementById('user-status');
    if (status) status.value = '0';
    var pass = document.getElementById('user-password');
    if (pass) {
        pass.value = '';
        pass.required = true;
    }
    var passLabel = document.getElementById('user-password-label');
    if (passLabel) passLabel.innerHTML = 'Password <span class="text-danger">*</span>';
    var passHelp = document.getElementById('user-password-help');
    if (passHelp) passHelp.style.display = 'none';
    var submitBtn = document.getElementById('user-submit-btn');
    if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Create User';
    var errorDiv = document.getElementById('user-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');
}

function openEditUserForm(id, name, email, role, isLocked) {
    var label = document.getElementById('userModalLabel');
    if (label) label.innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit User';
    var method = document.getElementById('user-method');
    if (method) method.value = 'PUT';
    var idEl = document.getElementById('user-id');
    if (idEl) idEl.value = id;
    var nameEl = document.getElementById('user-fullname');
    if (nameEl) nameEl.value = name;
    var emailEl = document.getElementById('user-email');
    if (emailEl) emailEl.value = email;
    var roleEl = document.getElementById('user-role');
    if (roleEl) roleEl.value = role;
    var statusEl = document.getElementById('user-status');
    if (statusEl) statusEl.value = isLocked ? '1' : '0';
    var pass = document.getElementById('user-password');
    if (pass) {
        pass.value = '';
        pass.required = false;
    }
    var passLabel = document.getElementById('user-password-label');
    if (passLabel) passLabel.innerHTML = 'New Password <span class="text-muted fw-normal">(optional)</span>';
    var passHelp = document.getElementById('user-password-help');
    if (passHelp) passHelp.style.display = 'block';
    var submitBtn = document.getElementById('user-submit-btn');
    if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Save Changes';
    var errorDiv = document.getElementById('user-modal-error');
    if (errorDiv) errorDiv.classList.add('d-none');

    var modalEl = document.getElementById('userModal');
    if (modalEl) {
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function requestFS() {
    var docEl = document.documentElement;
    if (docEl.requestFullscreen) {
        return docEl.requestFullscreen();
    } else if (docEl.webkitRequestFullscreen) {
        return docEl.webkitRequestFullscreen();
    } else if (docEl.mozRequestFullScreen) {
        return docEl.mozRequestFullScreen();
    } else if (docEl.msRequestFullscreen) {
        return docEl.msRequestFullscreen();
    }
    return Promise.reject();
}

function isCurrentlyFullscreen() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
}

function handleFSEvent() {
    if (!isCurrentlyFullscreen()) {
        sessionStorage.setItem('gims_fullscreen', '0');
        window.__gims_user_exited = true;
    } else {
        sessionStorage.setItem('gims_fullscreen', '1');
    }
}

document.addEventListener('fullscreenchange', handleFSEvent);
document.addEventListener('webkitfullscreenchange', handleFSEvent);
document.addEventListener('mozfullscreenchange', handleFSEvent);
document.addEventListener('MSFullscreenChange', handleFSEvent);

if (sessionStorage.getItem('gims_fullscreen') === '1') {
    requestFS().catch(function () {
        function reEnterFS(e) {
            if (e.key && e.key !== 'Escape') return;
            if (sessionStorage.getItem('gims_fullscreen') === '1' && !isCurrentlyFullscreen()) {
                requestFS().catch(function () {});
            }
            document.removeEventListener('click', reEnterFS, true);
            document.removeEventListener('keydown', reEnterFS, true);
        }
        document.addEventListener('click', reEnterFS, true);
        document.addEventListener('keydown', reEnterFS, true);
    });
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'f' || e.key === 'F') {
        var active = document.activeElement;
        var tag = active ? active.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || active.isContentEditable) return;
        if (!isCurrentlyFullscreen()) {
            window.__gims_user_exited = false;
            sessionStorage.setItem('gims_fullscreen', '1');
            requestFS().catch(function () {});
        }
    }
});

function formatUnitJs(qty, unit) {
    qty = Number(qty) || 0;
    unit = (unit || 'pcs').toLowerCase();
    var unitMap = {
        'kg': ['kg', 'kg'], 'g': ['g', 'g'], 'gm': ['gm', 'gm'],
        'can': ['can', 'cans'], 'bottle': ['bottle', 'bottles'],
        'pcs': ['pc', 'pcs'], 'pc': ['pc', 'pcs'], 'piece': ['piece', 'pieces'],
        'packet': ['packet', 'packets'], 'pkt': ['pkt', 'pkts'],
        'box': ['box', 'boxes'], 'ltr': ['ltr', 'ltr'], 'liter': ['liter', 'liters'],
        'litre': ['litre', 'litres'], 'bag': ['bag', 'bags'], 'sack': ['sack', 'sacks'],
        'pack': ['pack', 'packs'], 'carton': ['carton', 'cartons'],
        'jar': ['jar', 'jars'], 'tin': ['tin', 'tins']
    };
    var label = unitMap[unit] ? (qty === 1 ? unitMap[unit][0] : unitMap[unit][1]) : (qty === 1 || unit.endsWith('s') ? unit : unit + 's');
    return qty + ' ' + label;
}

function bindSPALinks() {
    document.querySelectorAll('a[href]').forEach(function (link) {
        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.includes('logout.php') || href.includes('setup.php') || href.startsWith('http') || href.startsWith('mailto:')) {
            return;
        }
        if (link.__spa_bound) return;
        link.__spa_bound = true;

        link.addEventListener('click', function (event) {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadPage(this.href, true);
        });
    });
}

function loadPage(url, pushState) {
    var wasFS = isCurrentlyFullscreen() || sessionStorage.getItem('gims_fullscreen') === '1';
    fetch(url)
        .then(function (response) { return response.text(); })
        .then(function (html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');

            var newMain = doc.querySelector('main');
            var currentMain = document.querySelector('main');
            if (newMain && currentMain) {
                currentMain.innerHTML = newMain.innerHTML;
            }

            var newNav = doc.querySelector('.gims-navbar');
            var currentNav = document.querySelector('.gims-navbar');
            if (newNav && currentNav) {
                currentNav.innerHTML = newNav.innerHTML;
            }

            if (doc.title) {
                document.title = doc.title;
            }

            if (pushState) {
                history.pushState({ url: url }, '', url);
            }

            window.scrollTo(0, 0);

            if (wasFS && !isCurrentlyFullscreen()) {
                requestFS().catch(function () {});
            }

            initPageScripts();
        })
        .catch(function () {
            window.location.href = url;
        });
}

function initPageScripts() {
    bindSPALinks();

    var productForm = document.getElementById('product-form');
    if (productForm && !productForm.__bound) {
        productForm.__bound = true;
        productForm.addEventListener('submit', function (event) {
            var name = document.getElementById('form-name').value.trim();
            var errorDiv = document.getElementById('modal-error');
            if (name === '') {
                event.preventDefault();
                errorDiv.textContent = 'Product name is required.';
                errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }
        });
    }

    var catForm = document.getElementById('category-form');
    if (catForm && !catForm.__bound) {
        catForm.__bound = true;
        catForm.addEventListener('submit', function (event) {
            var name = document.getElementById('cat-name').value.trim();
            var errorDiv = document.getElementById('cat-modal-error');
            if (name === '') {
                event.preventDefault();
                errorDiv.textContent = 'Category name is required.';
                errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }
        });
    }

    var supForm = document.getElementById('supplier-form');
    if (supForm && !supForm.__bound) {
        supForm.__bound = true;
        supForm.addEventListener('submit', function (event) {
            var name = document.getElementById('sup-name').value.trim();
            var errorDiv = document.getElementById('sup-modal-error');
            if (name === '') {
                event.preventDefault();
                errorDiv.textContent = 'Supplier name is required.';
                errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }
        });
    }

    var saleProductSelect  = document.getElementById('sale-product');
    var saleStockInfo      = document.getElementById('sale-stock-info');
    var saleQtyInput       = document.getElementById('sale-qty');
    var salePriceInput     = document.getElementById('sale-price');
    var saleDiscountInput  = document.getElementById('sale-discount');
    var saleDiscountType   = document.getElementById('sale-discount-type');
    var saleForm           = document.getElementById('sale-form');
    var saleLossWarning    = document.getElementById('sale-loss-warning');
    var saleLossDetail     = document.getElementById('sale-loss-detail');

    var currentCostPrice   = 0;
    var VAT_RATE           = 0.13;

    function getSaleSubmitBtn() {
        if (!saleForm) return null;
        return saleForm.querySelector('button[type="submit"]');
    }

    function updateSaleBreakdown() {
        var qty      = Math.max(0, parseInt((saleQtyInput  ? saleQtyInput.value  : '0'), 10) || 0);
        var price    = Math.max(0, parseFloat((salePriceInput ? salePriceInput.value : '0')) || 0);
        var disc     = Math.max(0, parseFloat((saleDiscountInput ? saleDiscountInput.value : '0')) || 0);
        var discType = saleDiscountType ? saleDiscountType.value : 'flat';

        var subtotal    = qty * price;
        var discAmt     = (discType === 'flat') ? Math.min(disc, subtotal) : subtotal * (disc / 100);
        var taxable     = Math.max(0, subtotal - discAmt);
        var vatAmt      = taxable * VAT_RATE;
        var netTotal    = taxable + vatAmt;

        var fmt = function(v) { return 'NRs. ' + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

        var previewSubtotal = document.getElementById('sale-preview-subtotal');
        var previewDiscount = document.getElementById('sale-preview-discount');
        var previewTaxable  = document.getElementById('sale-preview-taxable');
        var previewVat      = document.getElementById('sale-preview-vat');
        var previewTotal    = document.getElementById('sale-total-value');

        if (previewSubtotal) previewSubtotal.textContent = fmt(subtotal);
        if (previewDiscount) previewDiscount.textContent = '- ' + fmt(discAmt);
        if (previewTaxable)  previewTaxable.textContent  = fmt(taxable);
        if (previewVat)      previewVat.textContent       = '+ ' + fmt(vatAmt);
        if (previewTotal)    previewTotal.textContent     = fmt(netTotal);

        if (saleLossWarning && saleLossDetail && currentCostPrice > 0 && qty > 0) {
            var effectiveUnitPrice = price - (discType === 'flat' ? disc : price * disc / 100);
            if (effectiveUnitPrice < currentCostPrice) {
                saleLossWarning.classList.remove('d-none');
                saleLossDetail.textContent = 'Effective price NRs. ' + effectiveUnitPrice.toFixed(2) + ' is below cost price NRs. ' + currentCostPrice.toFixed(2) + '. This sale will result in a loss.';
            } else {
                saleLossWarning.classList.add('d-none');
            }
        } else if (saleLossWarning) {
            saleLossWarning.classList.add('d-none');
        }
    }

    function applyStockState(stock, unit, costPrice) {
        if (!saleStockInfo || !saleQtyInput) return;
        unit = unit || 'pcs';
        currentCostPrice = costPrice || 0;
        var submitBtn = getSaleSubmitBtn();
        var errorDiv  = document.getElementById('sale-modal-error');
        var qtyUnitBadge = document.getElementById('sale-qty-unit');
        if (qtyUnitBadge) qtyUnitBadge.textContent = unit;

        saleStockInfo.classList.remove('d-none', 'alert-info', 'alert-danger', 'alert-warning');

        if (stock <= 0) {
            saleStockInfo.classList.add('alert-danger');
            saleStockInfo.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i><strong>Out of Stock!</strong> There are 0 ' + unit + ' available. You cannot sell this product.';
            saleQtyInput.value = 0;
            saleQtyInput.disabled = true;
            saleQtyInput.max = 0;
            if (submitBtn) submitBtn.disabled = true;
            if (errorDiv) {
                errorDiv.textContent = 'This product is out of stock (0 ' + unit + ' available).';
                errorDiv.classList.remove('d-none');
            }
        } else {
            saleStockInfo.classList.add('alert-info');
            saleStockInfo.innerHTML = '<i class="bi bi-box-seam me-1"></i>Available in inventory: <strong id="sale-stock-value">' + stock + '</strong> <span id="sale-stock-unit">' + unit + '</span>';
            saleQtyInput.disabled = false;
            saleQtyInput.max = stock;
            saleQtyInput.min = 1;
            var currentVal = parseInt(saleQtyInput.value, 10) || 0;
            if (currentVal < 1 || currentVal > stock) {
                saleQtyInput.value = 1;
            }
            if (submitBtn) submitBtn.disabled = false;
            if (errorDiv) errorDiv.classList.add('d-none');
        }
        updateSaleBreakdown();
    }

    if (saleProductSelect && !saleProductSelect.__bound) {
        saleProductSelect.__bound = true;
        saleProductSelect.addEventListener('change', function () {
            var selected = saleProductSelect.options[saleProductSelect.selectedIndex];
            var submitBtn = getSaleSubmitBtn();

            if (selected && selected.value !== '') {
                var initialStock = parseInt(selected.getAttribute('data-stock'), 10) || 0;
                var initialUnit  = selected.getAttribute('data-unit') || 'pcs';
                var initialCost  = parseFloat(selected.getAttribute('data-cost')) || 0;
                applyStockState(initialStock, initialUnit, initialCost);

                fetch('../api/stock.php?id=' + selected.value)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && typeof data.quantity !== 'undefined') {
                            var liveUnit = data.unit || initialUnit;
                            var liveCost = data.cost_price || 0;
                            selected.setAttribute('data-cost', liveCost);
                            applyStockState(parseInt(data.quantity, 10) || 0, liveUnit, liveCost);

                            if (salePriceInput && data.sell_price) {
                                salePriceInput.value = data.sell_price;
                            }
                        }
                    })
                    .catch(function () {
                        applyStockState(initialStock, initialUnit, initialCost);
                    });

                var defaultPrice = parseFloat(selected.getAttribute('data-price')) || 0;
                if (salePriceInput && (salePriceInput.value === '0' || salePriceInput.value === '')) {
                    salePriceInput.value = defaultPrice;
                }
                updateSaleBreakdown();
            } else {
                if (saleStockInfo) saleStockInfo.classList.add('d-none');
                if (saleQtyInput) { saleQtyInput.disabled = false; saleQtyInput.value = 1; }
                var qtyUnitBadge = document.getElementById('sale-qty-unit');
                if (qtyUnitBadge) qtyUnitBadge.textContent = 'pcs';
                if (submitBtn) submitBtn.disabled = false;
                currentCostPrice = 0;
                updateSaleBreakdown();
            }
        });
    }

    if (saleQtyInput && !saleQtyInput.__bound) {
        saleQtyInput.__bound = true;
        saleQtyInput.addEventListener('input', function () {
            var selected = saleProductSelect ? saleProductSelect.options[saleProductSelect.selectedIndex] : null;
            var currentStock = selected && selected.value !== '' ? (parseInt(selected.getAttribute('data-stock'), 10) || 0) : 0;
            var unit = selected ? (selected.getAttribute('data-unit') || 'pcs') : 'pcs';
            var liveStockEl = document.getElementById('sale-stock-value');
            if (liveStockEl) currentStock = parseInt(liveStockEl.textContent, 10) || 0;

            var qty = parseInt(this.value, 10) || 0;
            var submitBtn = getSaleSubmitBtn();
            var errorDiv  = document.getElementById('sale-modal-error');

            if (currentStock <= 0) {
                if (errorDiv) { errorDiv.textContent = 'Product is out of stock (0 ' + unit + ' available).'; errorDiv.classList.remove('d-none'); }
                if (submitBtn) submitBtn.disabled = true;
            } else if (qty < 1) {
                if (errorDiv) { errorDiv.textContent = 'Quantity must be at least 1.'; errorDiv.classList.remove('d-none'); }
                if (submitBtn) submitBtn.disabled = true;
            } else if (qty > currentStock) {
                if (errorDiv) { errorDiv.textContent = 'Cannot sell ' + formatUnitJs(qty, unit) + '. Only ' + formatUnitJs(currentStock, unit) + ' available in stock.'; errorDiv.classList.remove('d-none'); }
                if (submitBtn) submitBtn.disabled = true;
            } else {
                if (errorDiv) errorDiv.classList.add('d-none');
                if (submitBtn) submitBtn.disabled = false;
            }
            updateSaleBreakdown();
        });
    }

    if (salePriceInput && !salePriceInput.__bound) { salePriceInput.__bound = true; salePriceInput.addEventListener('input', updateSaleBreakdown); }
    if (saleDiscountInput && !saleDiscountInput.__bound) { saleDiscountInput.__bound = true; saleDiscountInput.addEventListener('input', updateSaleBreakdown); }
    if (saleDiscountType && !saleDiscountType.__bound) { saleDiscountType.__bound = true; saleDiscountType.addEventListener('change', updateSaleBreakdown); }

    if (saleForm && !saleForm.__bound) {
        saleForm.__bound = true;
        saleForm.addEventListener('submit', function (event) {
            var product = document.getElementById('sale-product') ? document.getElementById('sale-product').value : '';
            var qty = saleQtyInput ? (parseInt(saleQtyInput.value, 10) || 0) : 0;
            var price = salePriceInput ? (parseFloat(salePriceInput.value) || 0) : 0;
            var disc  = saleDiscountInput ? (parseFloat(saleDiscountInput.value) || 0) : 0;
            var discType = saleDiscountType ? saleDiscountType.value : 'flat';
            var errorDiv = document.getElementById('sale-modal-error');
            var selected = saleProductSelect ? saleProductSelect.options[saleProductSelect.selectedIndex] : null;
            var availableStock = selected && selected.value !== '' ? (parseInt(selected.getAttribute('data-stock'), 10) || 0) : 0;
            var unit = selected ? (selected.getAttribute('data-unit') || 'pcs') : 'pcs';
            var liveStockEl = document.getElementById('sale-stock-value');
            if (liveStockEl) availableStock = parseInt(liveStockEl.textContent, 10) || 0;

            var discAmt = (discType === 'flat') ? disc : price * disc / 100;
            var effectivePrice = price - discAmt;

            if (!errorDiv) return;

            if (product === '') {
                event.preventDefault(); errorDiv.textContent = 'Please select a product.'; errorDiv.classList.remove('d-none');
            } else if (availableStock <= 0) {
                event.preventDefault(); errorDiv.textContent = 'Cannot sell this item because inventory is 0 (Out of stock).'; errorDiv.classList.remove('d-none');
            } else if (qty < 1) {
                event.preventDefault(); errorDiv.textContent = 'Quantity must be at least 1.'; errorDiv.classList.remove('d-none');
            } else if (qty > availableStock) {
                event.preventDefault(); errorDiv.textContent = 'Not enough stock available. Only ' + formatUnitJs(availableStock, unit) + ' in stock.'; errorDiv.classList.remove('d-none');
            } else if (effectivePrice < 0) {
                event.preventDefault(); errorDiv.textContent = 'Discount cannot exceed the unit price.'; errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }
        });
    }

    var purchProductSelect = document.getElementById('purch-product');
    var purchStockInfo = document.getElementById('current-stock-info');
    var purchStockValue = document.getElementById('current-stock-value');
    var purchStockUnit = document.getElementById('current-stock-unit');
    var purchQtyUnit = document.getElementById('purch-qty-unit');
    var purchForm = document.getElementById('purchase-form');

    if (purchProductSelect && !purchProductSelect.__bound) {
        purchProductSelect.__bound = true;
        purchProductSelect.addEventListener('change', function () {
            var selected = purchProductSelect.options[purchProductSelect.selectedIndex];
            if (selected && selected.value !== '') {
                var unit = selected.getAttribute('data-unit') || 'pcs';
                if (purchStockUnit) purchStockUnit.textContent = unit;
                if (purchQtyUnit) purchQtyUnit.textContent = unit;

                fetch('../api/stock.php?id=' + selected.value)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && typeof data.quantity !== 'undefined' && purchStockValue && purchStockInfo) {
                            purchStockValue.textContent = data.quantity;
                            if (purchStockUnit) purchStockUnit.textContent = data.unit || unit;
                            if (purchQtyUnit) purchQtyUnit.textContent = data.unit || unit;
                            purchStockInfo.classList.remove('d-none');
                        }
                    })
                    .catch(function () {
                        if (purchStockValue && purchStockInfo) {
                            purchStockValue.textContent = selected.getAttribute('data-stock') || '0';
                            purchStockInfo.classList.remove('d-none');
                        }
                    });
            } else {
                if (purchStockInfo) purchStockInfo.classList.add('d-none');
                if (purchQtyUnit) purchQtyUnit.textContent = 'pcs';
            }
        });
    }

    if (purchForm && !purchForm.__bound) {
        purchForm.__bound = true;
        purchForm.addEventListener('submit', function (event) {
            var product = document.getElementById('purch-product') ? document.getElementById('purch-product').value : '';
            var qty = document.getElementById('purch-qty') ? (parseInt(document.getElementById('purch-qty').value, 10) || 0) : 0;
            var cost = document.getElementById('purch-cost') ? (parseFloat(document.getElementById('purch-cost').value) || 0) : 0;
            var errorDiv = document.getElementById('purchase-modal-error');

            if (!errorDiv) return;

            if (product === '') {
                event.preventDefault();
                errorDiv.textContent = 'Please select a product.';
                errorDiv.classList.remove('d-none');
            } else if (qty < 1) {
                event.preventDefault();
                errorDiv.textContent = 'Quantity must be at least 1.';
                errorDiv.classList.remove('d-none');
            } else if (cost < 0) {
                event.preventDefault();
                errorDiv.textContent = 'Unit cost cannot be negative.';
                errorDiv.classList.remove('d-none');
            } else {
                errorDiv.classList.add('d-none');
            }
        });
    }

    var dashboardData = document.getElementById('dashboard-chart-data') || document.getElementById('chart-data');
    var stockCanvas = document.getElementById('stockChart');
    if (dashboardData && stockCanvas && typeof Chart !== 'undefined') {
        var rawLabels = dashboardData.getAttribute('data-labels');
        var rawValues = dashboardData.getAttribute('data-values');
        if (rawLabels && rawValues) {
            var labels = JSON.parse(rawLabels);
            var values = JSON.parse(rawValues);
            var existingChart = Chart.getChart(stockCanvas);
            if (existingChart) existingChart.destroy();
            new Chart(stockCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Stock Units',
                        data: values,
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.75)',
                            'rgba(13, 148, 136, 0.75)',
                            'rgba(16, 185, 129, 0.75)',
                            'rgba(245, 158, 11, 0.75)',
                            'rgba(239, 68, 68, 0.75)',
                            'rgba(139, 92, 246, 0.75)',
                            'rgba(100, 116, 139, 0.75)'
                        ],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.parsed.y + ' units in stock';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.7)' },
                            ticks: { precision: 0 }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    var dashRevCanvas = document.getElementById('dashRevenueChart');
    if (dashboardData && dashRevCanvas && typeof Chart !== 'undefined') {
        var rawDashRevLabels = dashboardData.getAttribute('data-rev-labels');
        var rawDashRevValues = dashboardData.getAttribute('data-rev-values');
        if (rawDashRevLabels && rawDashRevValues) {
            var dashRevLabels = JSON.parse(rawDashRevLabels);
            var dashRevVals = JSON.parse(rawDashRevValues);
            var existingDashRevChart = Chart.getChart(dashRevCanvas);
            if (existingDashRevChart) existingDashRevChart.destroy();
            new Chart(dashRevCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: dashRevLabels,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: dashRevVals,
                        borderColor: 'rgba(22, 163, 74, 0.95)',
                        backgroundColor: 'rgba(22, 163, 74, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgba(22, 163, 74, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return 'NRs. ' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.7)' },
                            ticks: { callback: function (v) { return 'NRs. ' + Number(v).toLocaleString(); } }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    var dashSalesCanvas = document.getElementById('dashSalesChart');
    if (dashboardData && dashSalesCanvas && typeof Chart !== 'undefined') {
        var rawDashRevLabels2 = dashboardData.getAttribute('data-rev-labels');
        var rawDashSalesVals = dashboardData.getAttribute('data-sales-values');
        if (rawDashRevLabels2 && rawDashSalesVals) {
            var dashSalesLabels = JSON.parse(rawDashRevLabels2);
            var dashSalesData = JSON.parse(rawDashSalesVals);
            var existingDashSalesChart = Chart.getChart(dashSalesCanvas);
            if (existingDashSalesChart) existingDashSalesChart.destroy();
            new Chart(dashSalesCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: dashSalesLabels,
                    datasets: [{
                        label: 'Transactions',
                        data: dashSalesData,
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderRadius: 5,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.parsed.y + ' sale(s)'; }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.7)' },
                            ticks: { precision: 0 }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    var reportsData = document.getElementById('report-chart-data') || document.getElementById('chart-data');
    var revCanvas = document.getElementById('revenueChart');
    var top10Canvas = document.getElementById('top10Chart');

    if (reportsData && revCanvas && typeof Chart !== 'undefined') {
        var rawRevLabels = reportsData.getAttribute('data-rev-labels');
        var rawRevValues = reportsData.getAttribute('data-rev-values');
        if (rawRevLabels && rawRevValues) {
            var revLabels = JSON.parse(rawRevLabels);
            var revValues = JSON.parse(rawRevValues);
            var existingRevChart = Chart.getChart(revCanvas);
            if (existingRevChart) existingRevChart.destroy();
            new Chart(revCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: revLabels,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: revValues,
                        borderColor: 'rgba(79, 70, 229, 0.95)',
                        backgroundColor: 'rgba(79, 70, 229, 0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return 'NRs. ' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.7)' },
                            ticks: {
                                callback: function (v) { return 'NRs. ' + Number(v).toLocaleString(); }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    if (reportsData && top10Canvas && typeof Chart !== 'undefined') {
        var rawTop10Labels = reportsData.getAttribute('data-top10-labels');
        var rawTop10Values = reportsData.getAttribute('data-top10-values');
        if (rawTop10Labels && rawTop10Values) {
            var top10Labels = JSON.parse(rawTop10Labels);
            var top10Values = JSON.parse(rawTop10Values);
            var palette = [
                'rgba(79, 70, 229, 0.8)', 'rgba(13, 148, 136, 0.8)', 'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(139, 92, 246, 0.8)',
                'rgba(37, 99, 235, 0.8)', 'rgba(234, 88, 12, 0.8)', 'rgba(15, 118, 110, 0.8)',
                'rgba(100, 116, 139, 0.8)'
            ];
            var existingTopChart = Chart.getChart(top10Canvas);
            if (existingTopChart) existingTopChart.destroy();
            new Chart(top10Canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: top10Labels,
                    datasets: [{
                        label: 'Units Sold',
                        data: top10Values,
                        backgroundColor: palette,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.parsed.x + ' units sold'; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    }

    document.querySelectorAll('.table-search-input').forEach(function (input) {
        if (!input.__bound) {
            input.__bound = true;
            input.addEventListener('input', function () {
                var filter = this.value.toLowerCase().trim();
                var targetTableId = this.getAttribute('data-table');
                var table = targetTableId ? document.getElementById(targetTableId) : this.closest('.content-card').querySelector('table');
                if (!table) return;
                var rows = table.querySelectorAll('tbody tr:not(.table-totals-row)');
                rows.forEach(function (row) {
                    if (row.querySelector('.empty-state')) return;
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    });

    var reportSearch = document.getElementById('reportTableSearch');
    if (reportSearch && !reportSearch.__bound) {
        reportSearch.__bound = true;
        reportSearch.addEventListener('input', function () {
            var filter = this.value.toLowerCase().trim();
            var table = document.getElementById('report-table');
            if (!table) return;
            var rows = table.querySelectorAll('tbody tr:not(.table-totals-row)');
            rows.forEach(function (row) {
                if (row.querySelector('.empty-state')) return;
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

window.addEventListener('popstate', function () {
    loadPage(window.location.href, false);
});

document.addEventListener('DOMContentLoaded', function () {
    initPageScripts();
});
