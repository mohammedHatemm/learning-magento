define([
    'jquery',
    'mage/url',
    'mage/translate'
], function ($, urlBuilder, $t) {
    'use strict';

    var citiesCache = null;

    return function (Component) {
        return Component.extend({
            /**
             * Initialize component
             */
            initialize: function () {
                this._super();

                var self = this;

                // Only apply to city fields
                if ((this.index === 'city' || this.inputName === 'city') &&
                    (this.parentScope && this.parentScope.indexOf('shippingAddress') !== -1 ||
                     this.parentScope && this.parentScope.indexOf('billingAddress') !== -1)) {

                    this.initBostaCities();
                }

                return this;
            },

            /**
             * Initialize Bosta cities dropdown for Egypt
             */
            initBostaCities: function () {
                var self = this;

                // Find country field
                var countryField = this.parentName + '.country_id';

                // Subscribe to country changes
                if (window.checkoutConfig && window.checkoutConfig.formKey) {
                    setTimeout(function () {
                        self.checkAndReplaceCityField();
                    }, 2000);
                }
            },

            /**
             * Check country and replace city field if Egypt
             */
            checkAndReplaceCityField: function () {
                var self = this;

                // Get country value
                var countryValue = this.getCountryValue();

                if (countryValue === 'EG') {
                    this.replaceWithDropdown();
                }

                // Watch for country changes via DOM
                $(document).on('change', 'select[name="country_id"]', function () {
                    if ($(this).val() === 'EG') {
                        self.replaceWithDropdown();
                    } else {
                        self.restoreTextField();
                    }
                });
            },

            /**
             * Get country field value
             */
            getCountryValue: function () {
                var countrySelector = 'select[name="country_id"]:visible';
                var $country = $(countrySelector).first();
                return $country.val() || '';
            },

            /**
             * Replace city input with dropdown
             */
            replaceWithDropdown: function () {
                var self = this;
                var $cityInput = $('#' + this.uid);

                // Check if already a select
                if ($cityInput.prop('tagName') === 'SELECT' && $cityInput.data('bosta-enabled')) {
                    return;
                }

                // Load cities
                this.loadBostaCities(function (cities) {
                    if (cities.length === 0) {
                        return;
                    }

                    // If already select, update options
                    if ($cityInput.prop('tagName') === 'SELECT') {
                        self.updateSelectOptions($cityInput, cities);
                        return;
                    }

                    // Create select
                    var $select = $('<select>');

                    // Copy all attributes
                    $.each($cityInput[0].attributes, function (idx, attr) {
                        if (attr.nodeName !== 'type') {
                            $select.attr(attr.nodeName, attr.nodeValue);
                        }
                    });

                    $select.attr('data-bosta-enabled', 'true');

                    // Add options
                    $select.append($('<option>').val('').text($t('-- Please Select City --')));

                    $.each(cities, function (index, city) {
                        $select.append($('<option>').val(city.value).text(city.label));
                    });

                    // Preserve value
                    var currentValue = $cityInput.val();
                    if (currentValue) {
                        $select.val(currentValue);
                    }

                    // Replace
                    $cityInput.replaceWith($select);

                    // Update component value immediately if there's a current value
                    if (currentValue) {
                        self.value(currentValue);
                        // Clear any existing validation errors
                        setTimeout(function() {
                            self.error(false);
                            $select.removeClass('mage-error');
                            $select.parent().find('div.mage-error').remove();
                        }, 100);
                    }

                    // Update component value on change
                    $select.on('change', function () {
                        var value = $(this).val();

                        // Update the component's value observable
                        self.value(value);

                        // Clear validation error in the component
                        if (value) {
                            self.error(false);
                            self.warn(false);
                        }

                        // Validate the field
                        self.validate();

                        // Trigger events for other listeners
                        $(this).trigger('blur');
                    });
                });
            },

            /**
             * Restore text field
             */
            restoreTextField: function () {
                var $field = $('#' + this.uid);

                if ($field.data('bosta-enabled')) {
                    var $input = $('<input>')
                        .attr('type', 'text')
                        .attr('name', $field.attr('name'))
                        .attr('id', $field.attr('id'))
                        .attr('class', $field.attr('class'))
                        .val($field.val());

                    // Copy validation attributes
                    if ($field.attr('data-validate')) {
                        $input.attr('data-validate', $field.attr('data-validate'));
                    }

                    $field.replaceWith($input);
                }
            },

            /**
             * Update select options
             */
            updateSelectOptions: function ($select, cities) {
                var currentValue = $select.val();

                $select.empty();
                $select.append($('<option>').val('').text($t('-- Please Select City --')));

                $.each(cities, function (index, city) {
                    $select.append($('<option>').val(city.value).text(city.label));
                });

                if (currentValue) {
                    $select.val(currentValue);
                }
            },

            /**
             * Load Bosta cities from API
             */
            loadBostaCities: function (callback) {
                if (citiesCache) {
                    callback(citiesCache);
                    return;
                }

                $.ajax({
                    url: urlBuilder.build('bosta/api/cities'),
                    type: 'GET',
                    dataType: 'json',
                    showLoader: false,
                    success: function (response) {
                        if (response.success && response.cities) {
                            citiesCache = response.cities;
                            callback(response.cities);
                        } else {
                            callback([]);
                        }
                    },
                    error: function () {
                        callback([]);
                    }
                });
            }
        });
    };
});
