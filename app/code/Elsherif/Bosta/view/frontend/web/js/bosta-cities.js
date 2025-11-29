define([
    'jquery',
    'mage/url',
    'knockout',
    'mage/translate'
], function ($, urlBuilder, ko, $t) {
    'use strict';

    return {
        citiesCache: null,
        zonesCache: {},

        init: function (cityFieldSelector, countryFieldSelector) {
            var self = this;

            $(document).ready(function () {
                self.watchCountryChange(cityFieldSelector, countryFieldSelector);
            });
        },

        watchCountryChange: function (cityFieldSelector, countryFieldSelector) {
            var self = this;

            // Initial check on page load
            setTimeout(function () {
                var countryCode = $(countryFieldSelector).val();
                if (countryCode === 'EG') {
                    self.replaceWithBostaCities(cityFieldSelector);
                }
            }, 1500);

            // Watch for country changes
            $(document).on('change', countryFieldSelector, function () {
                var countryCode = $(this).val();

                if (countryCode === 'EG') {
                    self.replaceWithBostaCities(cityFieldSelector);
                } else {
                    self.restoreOriginalField(cityFieldSelector);
                }
            });
        },

        /**
         * Replace city field with Bosta cities dropdown
         */
        replaceWithBostaCities: function (cityFieldSelector) {
            var self = this;
            var $cityField = $(cityFieldSelector).filter(':visible').first();

            if ($cityField.length === 0) {
                return;
            }

            // Check if already replaced
            if ($cityField.prop('tagName') === 'SELECT' && $cityField.data('bosta-enabled')) {
                return;
            }

            // Load Bosta cities
            self.loadBostaCities(function (cities) {
                if (cities.length === 0) {
                    console.error('No Bosta cities available');
                    return;
                }

                // If it's already a select, just update options
                if ($cityField.prop('tagName') === 'SELECT') {
                    self.updateSelectOptions($cityField, cities);
                    return;
                }

                // Save original value and parent
                var originalValue = $cityField.val();
                var $parent = $cityField.parent();

                // Create new select with all attributes from input
                var $select = $('<select>');

                // Copy all attributes except type
                $.each($cityField[0].attributes, function(idx, attr) {
                    if (attr.nodeName !== 'type') {
                        $select.attr(attr.nodeName, attr.nodeValue);
                    }
                });

                $select.attr('data-bosta-enabled', 'true');

                // Add placeholder option
                $select.append(
                    $('<option>')
                        .val('')
                        .text($t('-- Please select a city --'))
                );

                // Add city options
                $.each(cities, function (index, city) {
                    $select.append(
                        $('<option>')
                            .val(city.value)
                            .text(city.label)
                    );
                });

                // Set value if it exists
                if (originalValue) {
                    $select.val(originalValue);
                }

                // Replace field
                $cityField.replaceWith($select);

                // Force Knockout to recognize the change
                $select.on('change', function() {
                    var newValue = $(this).val();

                    // Trigger input event for Knockout
                    $(this).trigger('input');

                    // Remove validation errors
                    $(this).removeClass('mage-error');
                    $parent.find('div.mage-error').remove();
                    $(this).attr('aria-invalid', 'false');

                    // Trigger validation
                    if ($(this).validation) {
                        $(this).validation('clearError');
                    }

                    // Force Knockout update
                    var element = $(this)[0];
                    if (element && ko.dataFor(element)) {
                        $(this).trigger('change');
                        $(this).trigger('blur');
                    }
                });

                // Auto-clear validation on load if value exists
                if ($select.val()) {
                    setTimeout(function() {
                        $select.trigger('change');
                    }, 200);
                }
            });
        },

        updateSelectOptions: function($select, cities) {
            var currentValue = $select.val();

            $select.empty();

            $select.append(
                $('<option>')
                    .val('')
                    .text($t('-- Please select a city --'))
            );

            $.each(cities, function (index, city) {
                $select.append(
                    $('<option>')
                        .val(city.value)
                        .text(city.label)
                );
            });

            if (currentValue) {
                $select.val(currentValue);
            }
        },

        /**
         * Restore original text field
         */
        restoreOriginalField: function (cityFieldSelector) {
            var $field = $(cityFieldSelector);

            if ($field.data('bosta-enabled')) {
                var $input = $('<input>')
                    .attr('type', 'text')
                    .attr('name', $field.attr('name'))
                    .attr('id', $field.attr('id'))
                    .attr('class', $field.attr('class'))
                    .val('');

                $field.replaceWith($input);
            }
        },

        /**
         * Load Bosta cities from API
         */
        loadBostaCities: function (callback) {
            var self = this;

            if (self.citiesCache) {
                callback(self.citiesCache);
                return;
            }

            $.ajax({
                url: urlBuilder.build('bosta/api/cities'),
                type: 'GET',
                dataType: 'json',
                showLoader: true,
                success: function (response) {
                    if (response.success && response.cities) {
                        self.citiesCache = response.cities;
                        callback(response.cities);
                    } else {
                        console.error('Failed to load Bosta cities:', response.error);
                        callback([]);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading Bosta cities:', error);
                    callback([]);
                }
            });
        }
    };
});
