
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: LIB.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

var locationFinderClass = function(){
    var self = this;

    this.$elem         = null;
    this.$form         = null;
    this.option        = [];
    this.map           = null;
    this.mapClass      = null;
    this.latLng        = [37.7577627, -122.4726194]; // San Francisco, CA
    this.marker        = null;
    this.fromPost      = false;
    this.sync          = false;
    this.multifield    = false;
    this.lastPlaceID   = null;
    this.mappingFields = ['Country', 'Zip', 'State', 'City', 'Address'];
    this.mfHandler     = false;

    this.init = function(params){
        this.option = params;
        this.$elem  = $(this.option.mapElementID);
        this.$form  = $(this.$elem).closest('form');

        // Get default location from POST
        if (params.postLat !== false && params.postLng !== false) {
            this.latLng = [params.postLat, params.postLng];
            this.fromPost = true;
        }
        // Take from admin panel settings
        else if (params.defaultLocation.indexOf(',') > 0) {
            this.latLng = params.defaultLocation.split(',');
        }

        // Redefine zoom from post
        if (params.postZoom !== false) {
            this.option.zoom = params.postZoom;
        }

        // Define synchronization availability
        if (this.fieldsExist()) {
            this.sync = true;
        }

        // Define multifield plugins support
        this.defineMultifield();

        // Show map container
        $(params.containerID).removeClass('hide');

        this.buildMap();
    }

    this.destroy = function(){
        this.map.remove();

        $(this.elem).empty();

        this.map         = null;
        this.mapClass    = null;
        this.marker      = null;
        this.lastPlaceID = null;

        // Hide map container
        $(this.option.containerID).addClass('hide');

        flMap = new mapClass();
    }

    this.buildMap = function(){
        flMap.init(this.$elem, {
            zoom: this.option.zoom,
            center: this.latLng,
            userLocation: true,
            geocoder: {
                placeholder: lang['locationFinder_address_hint'],
                onSelect: this.onGeoSearch
            },
            addresses: [{
                latLng: this.latLng,
                content: lang['locationFinder_drag_notice']
            }],
            idle: function(map){
                self.map = map;
                self.mapClass = this;

                map.doubleClickZoom.disable(); 

                self.marker = this.markers[0];
                self.marker.dragging.enable();

                if (!self.fromPost) {
                    self.marker.openPopup();
                }

                self.setLocaton();
                self.setListeners();
            }
        });
    }

    this.onGeoSearch = function(address, lat, lng){
        self.latLng = [lat, lng];
        self.marker.setLatLng(new L.LatLng(lat, lng));

        self.update();
    }

    this.setListeners = function(){
        this.marker.on('dragend', function(){
            self.update();
        });
        this.map.on('zoom', function(){
           self.update(true);
        });
        this.map.on('dblclick', function(event){
            self.latLng = [event.latlng.lat, event.latlng.lng];
            self.marker.setLatLng(event.latlng);

            self.update();
        });
    }

    this.setLocaton = function(){
        // Don't update map location in edit mode
        if (this.fromPost) {
            return;
        }

        // Set location with help of browser 
        if (this.option.useVisitorLocation) {
            if (navigator.permissions) {
                navigator.permissions
                    .query({name: 'geolocation'})
                    .then(function(result) {
                        if (['granted', 'prompt'].indexOf(result.state) >= 0) {
                            self.setLocationFromNavigator();
                        } else {
                            self.setLocationByIP();
                        }
                    });
            }
        } else {
            this.update();
        }
    }

    this.setLocationFromNavigator = function(){
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position){
                self.latLng = [position.coords.latitude, position.coords.longitude];
                self.updateLocation();
            }), function(){
                self.setLocationByIP();
            };
        }
    }

    this.setLocationByIP = function(){
        if (!this.option.ipLocation || !this.option.ipLocation.replace(/[,\s]+/g, '').length) {
            this.update();
            return;
        }

        geocoder(this.option.ipLocation, function(response, status){
            if (status == 'success' && response.status == 'OK') {
                self.latLng = [response.results[0].lat, response.results[0].lng];
                self.updateLocation();
            }
        });
    }

    /**
     * Update map position
     *
     * @since 4.0.3 - noSync parameter added
     * @param  bool noSync - prevent synchronization
     */
    this.updateLocation = function(noSync){
        this.marker.setLatLng(this.latLng);
        this.map.panTo(this.latLng);

        this.update(noSync);
    }

    /**
     * Update location related data and synchronize fields location with map
     *
     * @since 4.0.3 - noSync parameter added
     * @param  bool noSync - prevent synchronization
     */
    this.update = function(noSync){
        // Update location data
        $('#lf_zoom').val(this.map.getZoom());
        $('#lf_lat').val(this.marker.getLatLng().lat);
        $('#lf_lng').val(this.marker.getLatLng().lng);

        if (noSync) {
            return;
        }

        // Synchronize the map location with dropdowns
        this.synchronise();
    }

    this.synchronise = function(){
        if (!this.sync) {
            return;
        }

        if (this.multifield && this.option.mapping) {
            this.multifieldSync();
        } else {
            this.staticSync();
        }
    }

    this.multifieldSync = function(){
        var field = this.option['mappingCountry'];

        this.getComponents(this.marker.getLatLng(), function(components){
            var place_id_city = null;
            var place_id_neighborhood = null;
            var text_data = {};

            components.forEach(function(item){
                switch (item.type) {
                    case 'city':
                        place_id_city = item.place_id;
                        break;

                    case 'suburb':
                        place_id_neighborhood = item.place_id;
                        break;

                    case 'postalcode':
                        text_data.Zip = item.location;;
                        break;
                }
            });

            text_data.Address = components[0].address;

            var place_id = self.option.useNeighborhood && place_id_neighborhood
                ? place_id_neighborhood
                : place_id_city;

            if (place_id && self.lastPlaceID != place_id) {
                // Save the latest place ID
                self.lastPlaceID = place_id;

                // Get format key by place ID
                var data = {
                    mode: 'locationFinder',
                    cityPlaceID: place_id_city,
                    neighborhoodPlaceID: place_id_neighborhood,
                };
                flUtil.ajax(data, function(response, status){
                    if (response.status == 'OK' && response.results) {
                        var index = 0;

                        var checkSet = function(){
                            if (index >= self.option.mfFields.length) {
                                return;
                            }

                            var field_key = self.option.mfFields[index];
                            var field_val = response.results.keys[index];

                            if (!field_val) {
                                return;
                            }

                            var $elem = self.$form.find('select[name="f[' + field_key + ']"]');

                            if (!$elem.attr('disabled')) {
                                $elem.val(field_val).trigger('change');
                                index++;
                                checkSet();
                            }

                            setTimeout(function(){
                                checkSet();
                            }, 200);
                        }

                        checkSet();
                    }
                });
            }

            for (var i in self.mappingFields) {
                var field_name = self.option['mapping' + self.mappingFields[i]];

                if (self.option.mfFields.indexOf(field_name) >= 0) {
                    continue;
                }

                $field = self.getField(self.mappingFields[i]);
                $field.val(text_data[self.mappingFields[i]]);
            }
        });
    }

    this.staticSync = function(){
        this.getComponents(this.marker.getLatLng(), function(components){
            var data = {};

            components.forEach(function(item){
                switch (item.type) {
                    case 'city':
                        data.City = item.location;
                        break;

                    case 'state':
                        data.State = item.location;;
                        break;

                    case 'country':
                        data.Country = item.location;;
                        break;

                    case 'postalcode':
                        data.Zip = item.location;;
                        break;
                }
            });

            data.Address = components[0].address;

            let timer = false;

            for (let i in self.mappingFields){
                i = parseInt(i);
                let name = self.mappingFields[i];
                let value = data[name];
                let $field = self.getField(name);
                let type = $field.prop('tagName').toLowerCase();

                if (i !== '0'
                    && $field.length
                    && type == 'select'
                    && $field.attr('disabled')
                    && !timer
                ) {
                    timer = true;

                    setTimeout(function(){
                        self.setValue(name, value, i, data);
                    }, 100);
                } else if (!$field.attr('disabled')) {
                    self.setValue(name, value);
                }
            }
        });
    }

    this.backSync = function(target){
        var address = [];

        for (var i in this.mappingFields) {
            $field = this.getField(this.mappingFields[i])
                         .filter(function(){return this.value})
                         .filter(':first');

            if ($field.val() === '0' || !$field.val() || !$field.is(':visible')) {
                continue;
            }

            var value = $field.prop('tagName').toLowerCase() == 'select'
                ? $field.find('option:selected').text()
                : $field.val();

            address.push(value);

            if ($field.get(0) == $(target).get(0)) {
                break;
            }
        };

        var query = address.reverse().join(', ');

        // Set the address to the search input
        this.$elem.find('.leaflet-autocomplete.leaflet-control').val(query);

        // Move map to the address
        geocoder(query, function(response, status){
            if (status == 'success' && response.status == 'OK') {
                self.latLng = [response.results[0].lat, response.results[0].lng];
                self.updateLocation(true);
            }
        });
    }

    this.onChangeListener = function(name){
        var $field = self.getField(name);

        $field.change(function(e){
            if (!e.originalEvent) {
                return;
            }

            self.backSync(this);
        }).on('select2:select', function(e){
            if (!e.params.originalEvent) {
                return;
            }

            self.backSync(this);
        });
    }

    /**
     * Interval for setValue()
     *
     * @type int
     * @since 5.1.0
     */
    this.setValueIterval = null;

    /**
     * Set static field value
     *
     * @since 5.1.0 - index and data parameters added
     */
    this.setValue = function(name, value, index, data){
        let $field = self.getField(name);

        if (!$field.length) {
            return;
        }

        if ($field.prop('tagName').toLowerCase() == 'select') {
            var foundValue = false;
            $field.find('> option').filter(function(){
                if (value && (
                        $(this).text() == value // Check by text name
                        || $(this).val().indexOf(value.replace(/\s+/g, '_').toLowerCase()) === 0 // Check by value key
                    )
                ) {
                    if ($field.val() != $(this).val()) {
                        $field.val($(this).val()).trigger('change');
                    }

                    foundValue = true;
                    clearInterval(self.setValueIterval);
                    self.setValueIterval = null;
                }
            });

            // Set value for the next dropdown field
            if (index) {
                if (foundValue) {
                    index++;

                    if (!self.mappingFields[index]) {
                        return;
                    }

                    name = self.mappingFields[index];
                    value = data[name];
                    let $nextField = self.getField(name);

                    if ($nextField.prop('tagName').toLowerCase() != 'select') {
                        return;
                    }
                }

                if (!self.setValueIterval) {
                    let iter = 0;
                    self.setValueIterval = setInterval(function(){
                        if (iter >= 25) {
                            clearInterval(self.setValueIterval);
                            return;
                        }

                        self.setValue(name, value, index, data);
                        iter++;
                    }, 200);
                }
            }
        } else {
            $field.val(value);
        }
    }

    this.getField = function(name){
        var field = this.option['mapping' + name];
        return this.$form.find('*[name^="f[' + field + ']"]');
    }

    this.getComponents = function(position, callback){
        var data = {
            latlng: position.lat + ',' + position.lng
        };

        geocoder(data, function(response, status){
            if (status == 'success') {
                if (response.status == 'OK' && response.results.length) {
                    callback.call(this, response.results);
                }
            } else {
                self.error('getComponents() failed, connection problems');
            }
        });
    }

    this.error = function(message){
        console.log('locationFinder: ' + message);
    }

    this.fieldsExist = function(){
        var exist = false;

        var index = 0;
        while (index < this.mappingFields.length) {
            var field = this.mappingFields[index];

            // Set field on change listener
            if (this.isFieldAvailable(field)) {
                exist = true;
                this.onChangeListener(field);
            }
            // Remove unavailable field
            else {
                this.mappingFields.splice(index, 1);
            }

            index++;
        }

        return exist;
    }

    this.isFieldAvailable = function(name){
        var field = this.option['mapping' + name];
        var exists = false;

        if (field) {
            exists = !!this.$form.find('*[name^="f[' + field + ']"]').length;
        }

        return exists;
    }

    this.defineMultifield = function(){
        var field = this.option['mappingCountry'];

        if (this.option.geocoding
            && typeof mfHandlerClass == 'function'
            && this.$form.find('select[name^="f[' + field + '_level"]').length
            && typeof mfFields == 'object'
        ) {
            this.multifield = true;
        }
    }
}
