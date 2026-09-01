(function () {
    function byId(id) {
        return document.getElementById(id);
    }

    function updateProgress(progress) {
        if (!progress) {
            return;
        }

        const overall = document.querySelector('[data-property-overall]');
        const missingList = document.querySelector('[data-property-missing-list]');
        const imageCount = document.querySelector('[data-property-image-count]');

        if (overall) {
            overall.textContent = progress.overall_percent || '0';
        }

        if (imageCount) {
            imageCount.textContent = progress.image_count || '0';
        }

        if (progress.step_meta) {
            Object.keys(progress.step_meta).forEach(function (stepKey) {
                const percentTarget = document.querySelector('[data-step-percent="' + stepKey + '"]');
                const badgeTarget = document.querySelector('[data-step-badge="' + stepKey + '"]');
                const percent = progress.step_meta[stepKey].percent;

                if (percentTarget) {
                    percentTarget.textContent = percent + '%';
                }

                if (badgeTarget) {
                    badgeTarget.textContent = percent + '%';
                }
            });
        }

        if (missingList) {
            missingList.innerHTML = '';

            if ((progress.missing || []).length === 0) {
                const item = document.createElement('li');
                item.textContent = 'Nothing missing. Listing is ready.';
                missingList.appendChild(item);
                return;
            }

            progress.missing.forEach(function (label) {
                const item = document.createElement('li');
                item.textContent = label;
                missingList.appendChild(item);
            });
        }
    }

    function showToast(icon, title, timer) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve();
        }

        return Swal.fire({
            icon: icon,
            title: title,
            showConfirmButton: false,
            timer: timer || 1000,
            timerProgressBar: true
        });
    }

    function showLoading(title) {
        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: title || 'Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });
    }

    async function postForm(form) {
        const response = await fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const raw = await response.text();
        let payload = null;

        try {
            payload = JSON.parse(raw);
        } catch (error) {
            throw new Error('Unexpected server response. Please refresh and try again.');
        }

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request failed.');
        }

        return payload;
    }

    function populateSelect(select, items, selectedId, placeholder) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        select.appendChild(empty);

        items.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.name;
            option.selected = Number(selectedId) === Number(item.id);
            select.appendChild(option);
        });
    }

    function initLocationSelectors() {
        if (!window.propertyWizardData) {
            return;
        }

        const countrySelect = document.querySelector('.js-country');
        const stateSelect = document.querySelector('.js-state');
        const citySelect = document.querySelector('.js-city');
        const localitySelect = document.querySelector('.js-locality');
        const selected = window.propertyWizardData.selected || {};

        function refreshStates() {
            const countryId = Number(countrySelect ? countrySelect.value : 0);
            const states = (window.propertyWizardData.states || []).filter(function (item) {
                return Number(item.country_id) === countryId;
            });

            populateSelect(stateSelect, states, selected.state_id, 'Select state');
            refreshCities();
        }

        function refreshCities() {
            const stateId = Number(stateSelect ? stateSelect.value : 0);
            const cities = (window.propertyWizardData.cities || []).filter(function (item) {
                return Number(item.state_id) === stateId;
            });

            populateSelect(citySelect, cities, selected.city_id, 'Select city');
            refreshLocalities();
        }

        function refreshLocalities() {
            const cityId = Number(citySelect ? citySelect.value : 0);
            const localities = (window.propertyWizardData.localities || []).filter(function (item) {
                return Number(item.city_id) === cityId;
            });

            populateSelect(localitySelect, localities, selected.locality_id, 'Select locality');
        }

        function applySelection(nextSelected) {
            if (typeof nextSelected.country_id !== 'undefined') {
                selected.country_id = Number(nextSelected.country_id) || 0;

                if (countrySelect) {
                    countrySelect.value = selected.country_id > 0 ? String(selected.country_id) : '';
                }
            }

            if (typeof nextSelected.state_id !== 'undefined') {
                selected.state_id = Number(nextSelected.state_id) || 0;
            }

            if (typeof nextSelected.city_id !== 'undefined') {
                selected.city_id = Number(nextSelected.city_id) || 0;
            }

            if (typeof nextSelected.locality_id !== 'undefined') {
                selected.locality_id = Number(nextSelected.locality_id) || 0;
            }

            refreshStates();

            if (stateSelect) {
                stateSelect.value = selected.state_id > 0 ? String(selected.state_id) : '';
            }

            refreshCities();

            if (citySelect) {
                citySelect.value = selected.city_id > 0 ? String(selected.city_id) : '';
            }

            refreshLocalities();

            if (localitySelect) {
                localitySelect.value = selected.locality_id > 0 ? String(selected.locality_id) : '';
            }
        }

        if (countrySelect) {
            countrySelect.addEventListener('change', function () {
                selected.state_id = 0;
                selected.city_id = 0;
                selected.locality_id = 0;
                refreshStates();
            });
        }

        if (stateSelect) {
            stateSelect.addEventListener('change', function () {
                selected.city_id = 0;
                selected.locality_id = 0;
                refreshCities();
            });
        }

        if (citySelect) {
            citySelect.addEventListener('change', function () {
                selected.locality_id = 0;
                refreshLocalities();
            });
        }

        refreshStates();
        if (stateSelect && selected.state_id) {
            stateSelect.value = String(selected.state_id);
            refreshCities();
        }
        if (citySelect && selected.city_id) {
            citySelect.value = String(selected.city_id);
            refreshLocalities();
        }
        if (localitySelect && selected.locality_id) {
            localitySelect.value = String(selected.locality_id);
        }

        window.propertyWizardLocation = {
            applySelection: applySelection,
            selected: selected
        };
    }

    function updateAreaUnitLabels(unit) {
        const normalizedUnit = unit || 'sq.ft';
        const labels = {
            builtup: 'Built-up Area',
            super_builtup: 'Super Built-up Area',
            carpet: 'Carpet Area',
            plot: 'Plot Area'
        };

        Object.keys(labels).forEach(function (key) {
            const target = document.querySelector('[data-area-label="' + key + '"]');

            if (!target) {
                return;
            }

            target.textContent = labels[key] + ' (' + normalizedUnit + ')';
        });
    }

    function formatIndianNumber(value) {
        const integerPart = Math.floor(Number(value) || 0).toString();

        if (integerPart.length <= 3) {
            return integerPart;
        }

        const lastThree = integerPart.slice(-3);
        const otherNumbers = integerPart.slice(0, -3);
        return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + lastThree;
    }

    function numberToIndianWords(value) {
        const number = Math.floor(Number(value) || 0);

        if (!Number.isFinite(number) || number <= 0) {
            return '';
        }

        const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        function twoDigits(num) {
            if (num < 20) {
                return ones[num];
            }

            const remainder = num % 10;
            return tens[Math.floor(num / 10)] + (remainder ? ' ' + ones[remainder] : '');
        }

        function threeDigits(num) {
            const hundred = Math.floor(num / 100);
            const remainder = num % 100;
            let words = '';

            if (hundred) {
                words += ones[hundred] + ' Hundred';
            }

            if (remainder) {
                words += (words ? ' ' : '') + twoDigits(remainder);
            }

            return words;
        }

        const parts = [
            { value: 10000000, label: 'Crore' },
            { value: 100000, label: 'Lakh' },
            { value: 1000, label: 'Thousand' },
            { value: 100, label: 'Hundred' }
        ];

        let remaining = number;
        let words = [];

        parts.forEach(function (part) {
            if (remaining >= part.value) {
                const chunk = Math.floor(remaining / part.value);

                if (part.value === 100) {
                    words.push(ones[chunk] + ' ' + part.label);
                } else {
                    words.push(twoDigits(chunk) + ' ' + part.label);
                }

                remaining %= part.value;
            }
        });

        if (remaining > 0) {
            words.push(remaining < 100 ? twoDigits(remaining) : threeDigits(remaining));
        }

        return words.join(' ').trim() + ' Rupees';
    }

    function updatePriceWords() {
        document.querySelectorAll('.js-price-input').forEach(function (input) {
            const target = document.querySelector('[data-price-words="' + input.dataset.priceWordsTarget + '"]');

            if (!target) {
                return;
            }

            const value = Number(input.value || 0);
            const maintenancePeriodSelect = document.querySelector('[name="maintenance_period"]');
            const maintenancePeriod = maintenancePeriodSelect ? maintenancePeriodSelect.value : '';

            if (!value) {
                target.textContent = input.dataset.priceWordsTarget === 'maintenance'
                    ? 'Optional maintenance charge. Choose monthly or yearly if applicable.'
                    : 'Enter amount to see formatted price.';
                return;
            }

            let suffix = '';

            if (input.dataset.priceWordsTarget === 'maintenance' && maintenancePeriod !== '') {
                suffix = maintenancePeriod === 'yearly' ? ' per year' : ' per month';
            }

            target.textContent = '₹' + formatIndianNumber(value) + ' (' + numberToIndianWords(value) + ')' + suffix;
        });
    }

    function renderDescriptionTemplates(templates, options) {
        const list = document.querySelector('[data-description-template-list]');
        const descriptionInput = byId('description');
        const shouldAutofill = options && options.autofill;

        if (!list) {
            return;
        }

        if (!Array.isArray(templates) || templates.length === 0) {
            list.innerHTML = '<div class="description-template-empty">Save a few details first to generate property description templates.</div>';
            return;
        }

        list.innerHTML = templates.map(function (template) {
            return (
                '<article class="description-template-card">' +
                    '<div class="description-template-head">' +
                        '<strong>' + template.title + '</strong>' +
                        '<button class="btn btn-dark btn-sm" type="button" data-description-template-use="' + template.id + '">Use Template</button>' +
                    '</div>' +
                    '<p data-description-template-content="' + template.id + '">' + template.content + '</p>' +
                '</article>'
            );
        }).join('');

        if (shouldAutofill && descriptionInput && descriptionInput.value.trim() === '' && templates[0] && templates[0].content) {
            descriptionInput.value = templates[0].content;
        }
    }

    async function fetchDescriptionTemplates(options) {
        const list = document.querySelector('[data-description-template-list]');
        const wizard = document.getElementById('property-wizard');
        const config = window.propertyWizardData || {};

        if (!list || !wizard || !config.description_templates_url) {
            return;
        }

        try {
            list.innerHTML = '<div class="description-template-empty">Generating professional description templates...</div>';
            const draftId = wizard.dataset.draftId || '';
            const response = await fetch(config.description_templates_url + '?draft_id=' + encodeURIComponent(draftId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to load description templates.');
            }

            renderDescriptionTemplates(payload.templates || [], options || {});
        } catch (error) {
            list.innerHTML = '<div class="description-template-empty">Description templates are not available right now.</div>';
        }
    }

    function initAreaUnitLabels() {
        const areaUnitSelect = document.querySelector('.js-area-unit');
        const initialUnit = (window.propertyWizardData && window.propertyWizardData.selected_area_unit) || 'sq.ft';

        updateAreaUnitLabels(initialUnit);

        if (!areaUnitSelect) {
            return;
        }

        areaUnitSelect.addEventListener('change', function () {
            const unit = areaUnitSelect.value || 'sq.ft';

            if (window.propertyWizardData) {
                window.propertyWizardData.selected_area_unit = unit;
            }

            updateAreaUnitLabels(unit);
        });
    }

    let googleMapsLoader = null;

    function loadGoogleMapsApi(apiKey) {
        if (!apiKey) {
            return Promise.reject(new Error('Google Maps API key is missing.'));
        }

        if (window.google && window.google.maps) {
            return Promise.resolve(window.google.maps);
        }

        if (googleMapsLoader) {
            return googleMapsLoader;
        }

        googleMapsLoader = new Promise(function (resolve, reject) {
            const callbackName = '__gharsquareInitGoogleMaps';
            const existingScript = document.querySelector('script[data-google-maps-loader="true"]');

            window[callbackName] = function () {
                resolve(window.google.maps);
                delete window[callbackName];
            };

            if (existingScript) {
                existingScript.addEventListener('error', function () {
                    reject(new Error('Unable to load Google Maps right now.'));
                });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&libraries=places&loading=async&callback=' + callbackName;
            script.async = true;
            script.defer = true;
            script.dataset.googleMapsLoader = 'true';
            script.onerror = function () {
                reject(new Error('Unable to load Google Maps right now.'));
                delete window[callbackName];
            };
            document.head.appendChild(script);
        });

        return googleMapsLoader;
    }

    function setFieldValue(selector, value) {
        const input = document.querySelector(selector);

        if (input) {
            input.value = value;
        }
    }

    function setMapAddressPreview(value) {
        const preview = document.querySelector('[data-map-address-preview]');
        const mapAddressInput = byId('map_address');

        if (mapAddressInput) {
            mapAddressInput.value = value;
        }

        if (preview) {
            preview.textContent = value || 'Not selected';
        }
    }

    function normalizeLocationName(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/&/g, ' and ')
            .replace(/[^\w\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function findLocationOptionByName(items, name, extraNames) {
        const targetNames = [name].concat(extraNames || []).map(normalizeLocationName).filter(Boolean);

        if (targetNames.length === 0) {
            return null;
        }

        let partialMatch = null;

        for (let index = 0; index < items.length; index += 1) {
            const item = items[index];
            const itemName = normalizeLocationName(item.name || '');

            if (targetNames.includes(itemName)) {
                return item;
            }

            if (!partialMatch) {
                const matched = targetNames.some(function (target) {
                    return itemName.includes(target) || target.includes(itemName);
                });

                if (matched) {
                    partialMatch = item;
                }
            }
        }

        return partialMatch;
    }

    function extractAddressComponent(result, typeNames) {
        const components = (result && result.address_components) || [];

        for (let index = 0; index < components.length; index += 1) {
            const component = components[index];
            const types = component.types || [];
            const hasType = typeNames.some(function (typeName) {
                return types.includes(typeName);
            });

            if (hasType) {
                return component;
            }
        }

        return null;
    }

    function syncLocationFieldsFromResult(result) {
        const wizardData = window.propertyWizardData || {};
        const locationRuntime = window.propertyWizardLocation;
        const countryComponent = extractAddressComponent(result, ['country']);
        const stateComponent = extractAddressComponent(result, ['administrative_area_level_1']);
        const cityComponent = extractAddressComponent(result, ['locality', 'administrative_area_level_2']);
        const localityComponent = extractAddressComponent(result, ['sublocality_level_1', 'sublocality', 'sublocality_level_2', 'neighborhood']);
        const postalCodeComponent = extractAddressComponent(result, ['postal_code']);
        const country = findLocationOptionByName(
            wizardData.countries || [],
            countryComponent ? countryComponent.long_name : '',
            countryComponent ? [countryComponent.short_name] : []
        );
        const states = (wizardData.states || []).filter(function (item) {
            return !country || Number(item.country_id) === Number(country.id);
        });
        const state = findLocationOptionByName(
            states,
            stateComponent ? stateComponent.long_name : '',
            stateComponent ? [stateComponent.short_name] : []
        );
        const cities = (wizardData.cities || []).filter(function (item) {
            return !state || Number(item.state_id) === Number(state.id);
        });
        const city = findLocationOptionByName(
            cities,
            cityComponent ? cityComponent.long_name : '',
            cityComponent ? [cityComponent.short_name] : []
        );
        const localities = (wizardData.localities || []).filter(function (item) {
            return !city || Number(item.city_id) === Number(city.id);
        });
        const locality = findLocationOptionByName(
            localities,
            localityComponent ? localityComponent.long_name : '',
            localityComponent ? [localityComponent.short_name] : []
        );

        if (locationRuntime && typeof locationRuntime.applySelection === 'function') {
            locationRuntime.applySelection({
                country_id: country ? country.id : 0,
                state_id: state ? state.id : 0,
                city_id: city ? city.id : 0,
                locality_id: locality ? locality.id : 0
            });
        }

        if (postalCodeComponent) {
            setFieldValue('#pincode', postalCodeComponent.long_name || '');
        }
    }

    function initLocationMap() {
        const wizardData = window.propertyWizardData || {};
        const googleMaps = wizardData.google_maps || {};
        const mapElement = byId('location_map');

        if (!mapElement || !googleMaps.enabled) {
            return;
        }

        loadGoogleMapsApi(googleMaps.api_key)
            .then(function (maps) {
                const locationData = wizardData.selected || {};
                const latitudeInput = byId('latitude');
                const longitudeInput = byId('longitude');
                const addressInput = byId('address_line');
                const searchInput = byId('map_search');
                const searchButton = byId('map_search_button');
                const useMapAddressButton = byId('use_map_address');
                const defaultCenter = {
                    lat: parseFloat(locationData.latitude || latitudeInput && latitudeInput.value || '28.6139') || 28.6139,
                    lng: parseFloat(locationData.longitude || longitudeInput && longitudeInput.value || '77.2090') || 77.2090
                };

                const map = new maps.Map(mapElement, {
                    center: defaultCenter,
                    zoom: locationData.latitude && locationData.longitude ? 16 : 11,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false
                });

                const marker = new maps.Marker({
                    map: map,
                    position: defaultCenter,
                    draggable: true,
                    animation: maps.Animation.DROP
                });

                const geocoder = new maps.Geocoder();

                function fillLatLng(latLng) {
                    const lat = typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat;
                    const lng = typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng;
                    setFieldValue('#latitude', Number(lat).toFixed(7));
                    setFieldValue('#longitude', Number(lng).toFixed(7));
                }

                function applyAddress(address) {
                    const formatted = address || '';
                    setMapAddressPreview(formatted);

                    if (searchInput && formatted !== '') {
                        searchInput.value = formatted;
                    }

                    if (addressInput && addressInput.value.trim() === '' && formatted !== '') {
                        addressInput.value = formatted;
                    }
                }

                function applyGeocodeResult(result, fallbackAddress) {
                    if (!result) {
                        applyAddress(fallbackAddress || '');
                        return;
                    }

                    const geometryLocation = result.geometry && result.geometry.location ? result.geometry.location : null;

                    if (geometryLocation) {
                        moveMarker(geometryLocation, false);
                    }

                    applyAddress(result.formatted_address || fallbackAddress || '');
                    syncLocationFieldsFromResult(result);
                }

                function reverseGeocode(latLng) {
                    geocoder.geocode({ location: latLng }, function (results, status) {
                        if (status === 'OK' && results && results.length > 0) {
                            const bestResult = results.find(function (item) {
                                const types = item.types || [];
                                return types.includes('street_address')
                                    || types.includes('premise')
                                    || types.includes('subpremise')
                                    || types.includes('route')
                                    || types.includes('plus_code');
                            }) || results[0];

                            applyAddress(bestResult.formatted_address || '');
                            syncLocationFieldsFromResult(bestResult);
                        } else {
                            const lat = typeof latLng.lat === 'function' ? latLng.lat() : latLng.lat;
                            const lng = typeof latLng.lng === 'function' ? latLng.lng() : latLng.lng;
                            applyAddress('Pinned at ' + Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6));
                        }
                    });
                }

                function moveMarker(latLng, shouldReverseGeocode) {
                    marker.setPosition(latLng);
                    map.panTo(latLng);
                    fillLatLng(latLng);

                    if (shouldReverseGeocode) {
                        reverseGeocode(latLng);
                    }
                }

                map.addListener('click', function (event) {
                    if (!event.latLng) {
                        return;
                    }

                    moveMarker(event.latLng, true);
                });

                marker.addListener('dragend', function (event) {
                    if (!event.latLng) {
                        return;
                    }

                    moveMarker(event.latLng, true);
                });

                function runAddressSearch() {
                    if (!searchInput) {
                        return;
                    }

                    const query = searchInput.value.trim();

                    if (query === '') {
                        return;
                    }

                    geocoder.geocode({ address: query }, function (results, status) {
                        if (status === 'OK' && results && results[0]) {
                            const result = results[0];
                            map.setZoom(16);
                            applyGeocodeResult(result, query);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Address not found',
                                text: 'Try a more specific location or move the pin on the map.',
                                timer: 1400,
                                showConfirmButton: false
                            });
                        }
                    });
                }

                if (searchInput && maps.places && typeof maps.places.Autocomplete === 'function') {
                    const autocomplete = new maps.places.Autocomplete(searchInput, {
                        fields: ['address_components', 'formatted_address', 'geometry', 'name'],
                        componentRestrictions: { country: ['in'] }
                    });

                    autocomplete.bindTo('bounds', map);
                    autocomplete.addListener('place_changed', function () {
                        const place = autocomplete.getPlace();

                        if (!place || !place.geometry || !place.geometry.location) {
                            runAddressSearch();
                            return;
                        }

                        map.setZoom(17);
                        applyGeocodeResult({
                            formatted_address: place.formatted_address || place.name || searchInput.value.trim(),
                            address_components: place.address_components || [],
                            geometry: {
                                location: place.geometry.location
                            }
                        }, searchInput.value.trim());
                    });
                }

                if (searchButton && searchInput) {
                    searchButton.addEventListener('click', function () {
                        runAddressSearch();
                    });

                    searchInput.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            runAddressSearch();
                        }
                    });
                }

                if (useMapAddressButton && addressInput) {
                    useMapAddressButton.addEventListener('click', function () {
                        const mapAddress = byId('map_address');
                        const pickedAddress = mapAddress ? mapAddress.value.trim() : '';

                        if (pickedAddress !== '') {
                            addressInput.value = pickedAddress;
                        }
                    });
                }

                if (locationData.map_address) {
                    applyAddress(locationData.map_address);
                } else if ((locationData.latitude || '').trim() !== '' && (locationData.longitude || '').trim() !== '') {
                    reverseGeocode(defaultCenter);
                } else {
                    fillLatLng(defaultCenter);
                }
            })
            .catch(function () {
                setMapAddressPreview('');
            });
    }

    function initBasicTypeChooser() {
        const listingInput = byId('listing_type_id');
        const propertyTypeInput = byId('property_type_id');
        const categoryInput = byId('property_category');
        const listingButtons = document.querySelectorAll('[data-listing-choice]');
        const categoryButtons = document.querySelectorAll('[data-category-choice]');
        const propertyTypeButtons = document.querySelectorAll('[data-property-type-choice]');
        const typeHeading = document.querySelector('[data-property-type-heading]');
        const typeCaption = document.querySelector('[data-property-type-caption]');
        const selectedListingText = document.querySelector('[data-selected-listing]');
        const selectedCategoryText = document.querySelector('[data-selected-category]');
        const selectedPropertyTypeText = document.querySelector('[data-selected-property-type]');
        const customPropertyTypeField = document.querySelector('[data-admin-custom-property-type]');
        const customPropertyTypeInput = byId('custom_property_type');
        const availableFromLabel = document.querySelector('[data-basic-required-label="available_from"]');

        if (!listingInput || !propertyTypeInput || propertyTypeButtons.length === 0) {
            return;
        }

        function questionText(category) {
            if (category === 'commercial') {
                return 'What kind of commercial property is it?';
            }

            if (category === 'land') {
                return 'What kind of plot / land is it?';
            }

            return 'What kind of residential property is it?';
        }

        function updateProfileRequiredLabels(category) {
            const isLand = category === 'land';

            document.querySelectorAll('[data-land-optional-label]').forEach(function (label) {
                label.classList.toggle('required-label', !isLand);
            });

            document.querySelectorAll('[data-residential-study-room]').forEach(function (wrapper) {
                const enabled = category === 'residential';
                const input = wrapper.querySelector('input[name="study_room"]');
                wrapper.hidden = !enabled;
                if (input) {
                    input.disabled = !enabled;
                    if (!enabled) input.checked = false;
                }
            });
        }

        function updatePricingRequiredLabels() {
            const selectedListingButton = Array.from(listingButtons).find(function (button) {
                return button.dataset.listingId === listingInput.value;
            });
            const listingLabel = selectedListingButton ? selectedListingButton.textContent.trim().toLowerCase() : '';
            const hasListingType = listingLabel !== '';
            const isSell = listingLabel === 'sell';
            const requiresAvailability = hasListingType && !isSell;
            const expectedPriceLabel = document.querySelector('[data-pricing-required-label="expected_price"]');
            const rentLabel = document.querySelector('[data-pricing-required-label="rent"]');
            const depositLabel = document.querySelector('[data-pricing-required-label="deposit"]');

            if (expectedPriceLabel) {
                expectedPriceLabel.classList.toggle('required-label', isSell);
            }

            if (rentLabel) {
                rentLabel.classList.toggle('required-label', !isSell && hasListingType);
            }

            if (depositLabel) {
                depositLabel.classList.toggle('required-label', !isSell && hasListingType);
            }

            if (availableFromLabel) {
                availableFromLabel.classList.toggle('required-label', requiresAvailability);
            }

            document.querySelectorAll('[data-pricing-mode-panel="sell"]').forEach(function (panel) {
                panel.hidden = !hasListingType || !isSell;
            });

            document.querySelectorAll('[data-pricing-mode-panel="rent"]').forEach(function (panel) {
                panel.hidden = !hasListingType || isSell;
            });
        }

        function updatePressedState(buttons, activeButton) {
            buttons.forEach(function (button) {
                const isActive = button === activeButton;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function updateCustomPropertyType() {
            const selectedButton = Array.from(propertyTypeButtons).find(function (button) {
                return button.dataset.propertyTypeId === propertyTypeInput.value;
            });
            const enabled = selectedButton && selectedButton.dataset.propertyTypeCustom === '1';

            if (customPropertyTypeField) {
                customPropertyTypeField.hidden = !enabled;
            }
            if (customPropertyTypeInput) {
                customPropertyTypeInput.disabled = !enabled;
                customPropertyTypeInput.required = Boolean(enabled);
            }
        }

        function applyCategory(category, preserveType) {
            const nextCategory = category || 'residential';
            let hasVisibleActiveType = false;

            if (categoryInput) {
                categoryInput.value = nextCategory;
            }

            if (selectedCategoryText) {
                const labels = (window.propertyWizardData && window.propertyWizardData.category_labels) || {};
                selectedCategoryText.textContent = labels[nextCategory] || 'Residential';
            }

            updateProfileRequiredLabels(nextCategory);

            categoryButtons.forEach(function (button) {
                const isActive = button.dataset.category === nextCategory;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            propertyTypeButtons.forEach(function (button) {
                const isVisible = button.dataset.propertyTypeCategory === nextCategory;
                const isSelected = propertyTypeInput.value !== '' && button.dataset.propertyTypeId === propertyTypeInput.value;

                button.hidden = !isVisible;
                button.classList.toggle('is-active', isVisible && isSelected);
                button.setAttribute('aria-pressed', isVisible && isSelected ? 'true' : 'false');

                if (isVisible && isSelected) {
                    hasVisibleActiveType = true;
                }
            });

            if (!preserveType || !hasVisibleActiveType) {
                propertyTypeInput.value = '';
                propertyTypeButtons.forEach(function (button) {
                    button.classList.remove('is-active');
                    button.setAttribute('aria-pressed', 'false');
                });

                if (selectedPropertyTypeText) {
                    selectedPropertyTypeText.textContent = 'Not selected';
                }
            }

            updateCustomPropertyType();

            if (typeHeading) {
                typeHeading.textContent = questionText(nextCategory);
            }

            if (typeCaption) {
                const labels = (window.propertyWizardData && window.propertyWizardData.category_labels) || {};
                const categoryLabel = labels[nextCategory] || 'property';
                typeCaption.textContent = 'Choose the closest ' + categoryLabel.toLowerCase() + ' type for this listing.';
            }

            syncAdminAmenities(nextCategory);
            syncAdminProfileDetails();
        }

        listingButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                listingInput.value = button.dataset.listingId || '';
                updatePressedState(listingButtons, button);

                if (selectedListingText) {
                    selectedListingText.textContent = button.textContent.trim() || 'Not selected';
                }

                updatePricingRequiredLabels();
                syncAdminProfileDetails();
            });
        });

        categoryButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                applyCategory(button.dataset.category || 'residential', false);
            });
        });

        propertyTypeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const typeId = button.dataset.propertyTypeId || '';
                const category = button.dataset.propertyTypeCategory || 'residential';

                propertyTypeInput.value = typeId;
                applyCategory(category, true);

                propertyTypeButtons.forEach(function (item) {
                    const isActive = item.dataset.propertyTypeId === typeId;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (selectedPropertyTypeText) {
                    selectedPropertyTypeText.textContent = button.textContent.trim() || 'Not selected';
                }
                updateCustomPropertyType();
            });
        });

        if (listingInput.value !== '') {
            listingButtons.forEach(function (button) {
                const isActive = button.dataset.listingId === listingInput.value;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        applyCategory((categoryInput && categoryInput.value) || 'residential', propertyTypeInput.value !== '');
        updatePricingRequiredLabels();
        updatePriceWords();

        const furnishingSelect = document.querySelector('[data-step-panel="profile"] [name="furnishing"]');
        if (furnishingSelect) {
            furnishingSelect.addEventListener('change', function () {
                const selectedItems = document.querySelectorAll('[data-admin-furnishing-item]:checked');
                if (furnishingSelect.value === 'fully' && selectedItems.length === 0) {
                    const defaults = ['light_fan', 'wardrobe', 'bed', 'sofa', 'dining_table', 'modular_kitchen', 'geyser', 'ac', 'curtains', 'tv', 'fridge', 'washing_machine'];
                    document.querySelectorAll('[data-admin-furnishing-item]').forEach(function (input) {
                        input.checked = defaults.includes(input.value);
                    });
                }
                syncAdminProfileDetails();
                const count = document.querySelectorAll('[data-admin-furnishing-item]:checked:not(:disabled)').length;
                const target = document.querySelector('[data-admin-furnishing-count]');
                if (target) target.textContent = count + ' selected';
            });
        }
        document.querySelectorAll('[data-admin-furnishing-item]').forEach(function (input) {
            input.addEventListener('change', function () {
                const count = document.querySelectorAll('[data-admin-furnishing-item]:checked').length;
                const target = document.querySelector('[data-admin-furnishing-count]');
                if (target) target.textContent = count + ' selected';
            });
        });
    }

    function scrollToStep(stepKey) {
        const panel = document.querySelector('[data-step-panel="' + stepKey + '"]');

        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function syncAdminAmenities(category) {
        const currentCategory = category || (byId('property_category') ? byId('property_category').value : '') || 'residential';
        document.querySelectorAll('[data-admin-amenity-categories]').forEach(function (option) {
            const categories = (option.dataset.adminAmenityCategories || '').split(/\s+/).filter(Boolean);
            const visible = categories.length === 0 || categories.includes(currentCategory);
            option.hidden = !visible;
            const input = option.querySelector('input');
            if (input) input.disabled = !visible;
        });
        document.querySelectorAll('[data-admin-amenity-group]').forEach(function (group) {
            group.hidden = !group.querySelector('[data-admin-amenity-categories]:not([hidden])');
        });
    }

    function syncAdminProfileDetails() {
        const category = (byId('property_category') ? byId('property_category').value : '') || 'residential';
        const propertyTypeId = byId('property_type_id') ? byId('property_type_id').value : '';
        const listingTypeId = byId('listing_type_id') ? byId('listing_type_id').value : '';
        const propertyButton = document.querySelector('[data-property-type-choice][data-property-type-id="' + propertyTypeId + '"]');
        const listingButton = document.querySelector('[data-listing-choice][data-listing-id="' + listingTypeId + '"]');
        const propertyName = (propertyButton && propertyButton.dataset.propertyTypeName || '').toLowerCase();
        const listingName = (listingButton && listingButton.dataset.listingName || '').toLowerCase();
        const isOffice = category === 'commercial' && (propertyName.includes('office') || propertyName.includes('co-working') || propertyName.includes('coworking'));
        const isPg = listingName === 'pg' || listingName.includes('paying guest') || listingName.includes('co-living');
        const form = document.querySelector('[data-step-panel="profile"] form');
        if (!form) return;

        function toggleSection(selector, visible) {
            const section = form.querySelector(selector);
            if (!section) return;
            section.hidden = !visible;
            section.querySelectorAll('input, select, textarea').forEach(function (control) {
                control.disabled = !visible;
            });
        }

        function toggleField(name, visible) {
            const control = form.querySelector('[name="' + name + '"]');
            const wrapper = control ? control.closest('.form-field') : null;
            if (wrapper) wrapper.hidden = !visible;
            if (control) control.disabled = !visible;
        }

        ['builtup_area', 'super_builtup_area', 'carpet_area'].forEach(function (name) { toggleField(name, category !== 'land'); });
        toggleField('plot_area', category === 'land' || category === 'commercial');
        ['bedrooms', 'bathrooms', 'balconies'].forEach(function (name) { toggleField(name, category === 'residential' && !isPg); });
        toggleField('parking_count', category !== 'land' && !isOffice && !isPg);
        ['floor_no', 'total_floor'].forEach(function (name) { toggleField(name, category !== 'land'); });
        toggleField('furnishing', category !== 'land' && !isOffice);
        toggleField('property_age', category !== 'land' && !isOffice);
        toggleField('facing', !isOffice && !isPg);
        toggleField('ownership_type', !isOffice && !isPg);

        toggleSection('[data-admin-pg-profile]', isPg);
        toggleSection('[data-admin-office-profile]', isOffice);
        const furnishing = form.querySelector('[name="furnishing"]');
        toggleSection('[data-admin-furnishing-panel]', !isOffice && category !== 'land' && ['semi', 'fully'].includes(furnishing ? furnishing.value : ''));
        const pgFurnishing = ['ac', 'bed', 'light_fan', 'geyser', 'curtains', 'wardrobe', 'tv', 'fridge', 'washing_machine'];
        form.querySelectorAll('[data-admin-furnishing-value]').forEach(function (option) {
            const visible = !isPg || pgFurnishing.includes(option.dataset.adminFurnishingValue || '');
            option.hidden = !visible;
            const input = option.querySelector('input');
            if (input) input.disabled = !visible;
        });
        const extraRooms = form.querySelector('[name="servant_room"]')?.closest('.form-field');
        if (extraRooms) extraRooms.hidden = category !== 'residential' || isPg;
    }

    async function handleStepForm(form, options) {
        const settings = options || {};
        const loadingTitle = settings.loadingTitle || 'Saving step...';
        const successMessage = settings.successMessage || null;
        const shouldRefreshTemplates = settings.refreshTemplates !== false;

        showLoading(loadingTitle);

        try {
            const payload = await postForm(form);
            updateProgress(payload.progress || null);

            if (shouldRefreshTemplates) {
                await fetchDescriptionTemplates({ autofill: true });
            }

            await showToast('success', successMessage || payload.message || 'Step saved.', 1000);
        } catch (error) {
            await showToast('error', error.message || 'Unable to save step.', 1600);
        }
    }

    function refreshMediaGrid(payload) {
        const mediaGrid = document.querySelector('[data-property-media-grid]');

        if (!mediaGrid || !payload || !payload.grid_html) {
            return;
        }

        mediaGrid.innerHTML = payload.grid_html;

        if (window.AdminUi && typeof window.AdminUi.initTooltips === 'function') {
            window.AdminUi.initTooltips(mediaGrid);
        }
    }

    function validateVideoFiles(files) {
        const maxBytes = 20 * 1024 * 1024;
        const checks = Array.from(files || []).map(function (file) {
            return new Promise(function (resolve, reject) {
                if (file.size > maxBytes) {
                    reject(new Error(file.name + ' is larger than 20 MB.'));
                    return;
                }

                const video = document.createElement('video');
                video.preload = 'metadata';
                video.onloadedmetadata = function () {
                    URL.revokeObjectURL(video.src);

                    if (video.duration > 30) {
                        reject(new Error(file.name + ' must be 30 seconds or shorter.'));
                        return;
                    }

                    resolve(true);
                };
                video.onerror = function () {
                    URL.revokeObjectURL(video.src);
                    reject(new Error('Unable to read video duration for ' + file.name + '.'));
                };
                video.src = URL.createObjectURL(file);
            });
        });

        return Promise.all(checks);
    }

    function setMediaUploadState(form, active, percent, message) {
        let indicator = form.querySelector('[data-media-upload-progress]');

        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'media-upload-progress';
            indicator.dataset.mediaUploadProgress = '';
            indicator.innerHTML =
                '<span class="media-upload-spinner" aria-hidden="true"></span>' +
                '<div><strong data-media-upload-message>Uploading...</strong>' +
                '<div class="media-upload-track"><span data-media-upload-bar></span></div>' +
                '<small data-media-upload-percent>0%</small></div>';
            form.appendChild(indicator);
        }

        const normalizedPercent = Math.max(0, Math.min(100, Math.round(percent || 0)));
        form.classList.toggle('is-uploading', active);
        indicator.hidden = !active;
        indicator.querySelector('[data-media-upload-message]').textContent = message || 'Uploading...';
        indicator.querySelector('[data-media-upload-bar]').style.width = normalizedPercent + '%';
        indicator.querySelector('[data-media-upload-percent]').textContent = normalizedPercent + '%';
        form.querySelectorAll('input, button').forEach(function (control) {
            control.disabled = active;
        });
    }

    function uploadMediaForm(form, data, onProgress) {
        return new Promise(function (resolve, reject) {
            const request = new XMLHttpRequest();
            request.open((form.method || 'POST').toUpperCase(), form.action);
            request.setRequestHeader('Accept', 'application/json');
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    onProgress((event.loaded / event.total) * 100);
                }
            });
            request.addEventListener('load', function () {
                try {
                    const payload = JSON.parse(request.responseText || '{}');
                    if (request.status < 200 || request.status >= 300 || !payload.success) {
                        reject(new Error(payload.message || 'Unable to upload media.'));
                        return;
                    }
                    resolve(payload);
                } catch (error) {
                    reject(new Error('Unexpected server response. Please refresh and try again.'));
                }
            });
            request.addEventListener('error', function () {
                reject(new Error('Upload interrupted. Check your connection and try again.'));
            });
            request.addEventListener('abort', function () {
                reject(new Error('Upload cancelled.'));
            });
            request.send(data);
        });
    }

    async function handleMediaForm(form) {
        if (form.dataset.uploading === 'true') {
            return;
        }

        const uploadKind = form.dataset.uploadKind || '';
        const formData = new FormData(form);
        form.dataset.uploading = 'true';
        setMediaUploadState(form, true, 0, uploadKind === 'youtube' ? 'Adding video...' : 'Preparing files...');

        try {
            if (uploadKind === 'video') {
                const videoInput = form.querySelector('input[type="file"]');

                if (videoInput && videoInput.files && videoInput.files.length > 0) {
                    await validateVideoFiles(videoInput.files);
                }
            }

            const payload = await uploadMediaForm(form, formData, function (percent) {
                const message = percent >= 100
                    ? (uploadKind === 'image' ? 'Compressing and cropping images...' : 'Processing media...')
                    : 'Uploading media...';
                setMediaUploadState(form, true, percent, message);
            });
            refreshMediaGrid(payload);
            const fileInput = form.querySelector('input[type="file"]');
            const youtubeInput = form.querySelector('input[name="youtube_url"]');

            if (fileInput) {
                fileInput.value = '';
            }

            if (youtubeInput) {
                youtubeInput.value = '';
            }

            document.querySelectorAll('.media-dropzone').forEach(function (dropzone) {
                dropzone.classList.remove('is-dragover');
            });

            updateProgress(payload.progress || null);
            await showToast('success', payload.message || 'Media uploaded.', 1000);
        } catch (error) {
            await showToast('error', error.message || 'Unable to upload media.', 1600);
        } finally {
            delete form.dataset.uploading;
            setMediaUploadState(form, false);
        }
    }

    async function handleMediaDelete(form) {
        showLoading('Removing media...');

        try {
            const payload = await postForm(form);
            refreshMediaGrid(payload);
            updateProgress(payload.progress || null);
            await showToast('success', payload.message || 'Media removed.', 1000);
        } catch (error) {
            await showToast('error', error.message || 'Unable to remove media.', 1600);
        }
    }

    async function handleMediaMetaForm(form) {
        try {
            const payload = await postForm(form);
            refreshMediaGrid(payload);
            updateProgress(payload.progress || null);
            await showToast('success', payload.message || 'Media updated.', 1000);
        } catch (error) {
            await showToast('error', error.message || 'Unable to update media.', 1600);
        }
    }

    async function handleSubmitForm(form) {
        showLoading('Submitting listing...');

        try {
            const payload = await postForm(form);
            updateProgress(payload.progress || null);
            await showToast('success', payload.message || 'Listing submitted.', 1000);

            if (payload.redirect_url) {
                window.location.href = payload.redirect_url;
            }
        } catch (error) {
            await showToast('error', error.message || 'Unable to submit listing.', 1800);
        }
    }

    function initWizard() {
        const wizard = document.getElementById('property-wizard');

        if (!wizard) {
            return;
        }

        initLocationSelectors();
        initBasicTypeChooser();
        initAreaUnitLabels();
        initLocationMap();
        updatePriceWords();
        fetchDescriptionTemplates({ autofill: true });

        document.addEventListener('click', function (event) {
            const stepButton = event.target.closest('[data-step-target]');
            const nextButton = event.target.closest('[data-next-step]');
            const refreshDescriptionsButton = event.target.closest('[data-description-refresh]');
            const useDescriptionButton = event.target.closest('[data-description-template-use]');

            if (stepButton) {
                scrollToStep(stepButton.dataset.stepTarget || '');
            }

            if (nextButton) {
                scrollToStep(nextButton.dataset.nextStep || '');
            }

            if (refreshDescriptionsButton) {
                fetchDescriptionTemplates({ autofill: false });
            }

            if (useDescriptionButton) {
                const templateId = useDescriptionButton.dataset.descriptionTemplateUse || '';
                const content = document.querySelector('[data-description-template-content="' + templateId + '"]');
                const descriptionInput = byId('description');
                if (content && descriptionInput) {
                    descriptionInput.value = content.textContent.trim();
                    showToast('success', 'Description template inserted.', 900);
                }
            }
        });

        document.addEventListener('input', function (event) {
            if (event.target instanceof HTMLInputElement && event.target.type === 'number') {
                const nextValue = event.target.value.trim();

                if (nextValue !== '' && Number(nextValue) < 0) {
                    event.target.value = event.target.min !== '' ? event.target.min : '';
                }
            }

            if (event.target instanceof HTMLInputElement && event.target.classList.contains('js-price-input')) {
                updatePriceWords();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!(event.target instanceof HTMLInputElement) || event.target.type !== 'number') {
                return;
            }

            if (event.key === '-' || event.key === 'Minus' || event.key === 'Subtract') {
                event.preventDefault();
            }
        });

        document.addEventListener('change', function (event) {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target instanceof HTMLInputElement && target.type === 'file') {
                const form = target.closest('form');

                if (form && form.dataset.customHandler === 'property-media-upload' && target.files && target.files.length > 0) {
                    handleMediaForm(form);
                }
            }

            if (target instanceof HTMLSelectElement && target.hasAttribute('data-media-auto-submit')) {
                const form = target.closest('form');

                if (form) {
                    handleMediaMetaForm(form);
                }
            }

            if (target instanceof HTMLSelectElement && target.name === 'maintenance_period') {
                updatePriceWords();
            }
        });

        document.querySelectorAll('.media-dropzone').forEach(function (dropzone) {
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });

            dropzone.addEventListener('drop', function (event) {
                const files = event.dataTransfer ? event.dataTransfer.files : null;
                const input = dropzone.querySelector('input[type="file"]');

                if (!files || !input) {
                    return;
                }

                const transfer = new DataTransfer();
                Array.from(files).forEach(function (file) {
                    transfer.items.add(file);
                });
                input.files = transfer.files;

                const form = input.closest('form');

                if (form && input.files.length > 0) {
                    handleMediaForm(form);
                }
            });
        });

        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.dataset.customHandler === 'property-step') {
                event.preventDefault();
                handleStepForm(form);
            }

            if (form.dataset.customHandler === 'property-media-upload') {
                event.preventDefault();
                handleMediaForm(form);
            }

            if (form.dataset.customHandler === 'property-submit') {
                event.preventDefault();
                handleSubmitForm(form);
            }

            if (form.dataset.customHandler === 'property-media-delete') {
                event.preventDefault();
                handleMediaDelete(form);
            }

            if (form.dataset.customHandler === 'property-media-meta') {
                event.preventDefault();
                handleMediaMetaForm(form);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initWizard);
})();
