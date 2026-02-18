
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: UTIL.JS
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

var flUtilClass = function(){
    /**
     * @since 4.9.1
     *
     * @type {flUtilClass}
     */
    let self = this;

    /**
     * Last ajax request
     *
     * @type object
     */
    this.ajaxRequest = null;

    /**
     * Last ajax request key
     *
     * @type String
     */
    this.ajaxKey = null;

    /**
     * Media points data
     *
     * @type Array
     */
    this.media_points = {
        all_tablet_mobile: 'screen and (max-width: 991px)'
    };

    /**
     * Delay of slow internet
     * @since 4.7.0
     * @type  {Number}
     */
    this.loadingDelay = 300;

    /**
     * Array of function which can modify ajax data before ajax call
     *
     * @since 4.8.2
     * @type Array
     */
    this.modifyDataFunctions = [];

    /**
     * Initial class method
     *
     */
    this.init = function() {
        $.ajaxSetup({
            crossDomain: true,
            xhrFields: {
                withCredentials: true
            }
        });

        self.markLoadedScripts();
        self.markLoadedStyles();
    };

    /**
     * Mark all document loaded scripts to avoid it's repeat
     * uploading by loadScript method
     */
    this.markLoadedScripts = function() {
        var scripts = document.getElementsByTagName('script');

        for (var i in scripts) {
            if (!scripts[i].src || typeof scripts[i].onload == 'function') {
                continue;
            }

            scripts[i].onload = (function(i){
                scripts[i].loaded = true;
            })(i);
        }
    };

    /**
     * Mark all document loaded styles to avoid it's repeat uploading by loadStyle method
     *
     * @since 4.9.0
     */
    this.markLoadedStyles = function() {
        var styles = document.querySelectorAll('link[rel="stylesheet"]');

        for (var i in styles) {
            if (!styles[i].href || typeof styles[i].onload === 'function') {
                continue;
            }

            styles[i].onload = (function(i){
                styles[i].loaded = true;
            })(i);
        }
    };

    /**
     * Do ajax call
     *
     * @param array    - ajax request data
     * @param function - callback function
     * @param boolean  - is get request
     */
    this.ajax = function(data, callback, get) {
        // Abort the previous query if it's still in progress
        if (self.ajaxRequest
            && data.ajaxKey
            && data.ajaxKey == this.ajaxKey
        ) {
            self.ajaxRequest.abort();
        }

        // Apply data modifiers
        if (self.modifyDataFunctions.length) {
            for (var i in self.modifyDataFunctions) {
                if (typeof self.modifyDataFunctions[i] == 'function') {
                    self.modifyDataFunctions[i].call(this, data, callback, get)
                }
            }
        }

        if (!data.mode) {
            console.log('flynax.ajax - no "mode" index in the data parameter found, "mode" is required');
            return;
        }

        if (typeof callback != 'function') {
            console.log('flynax.ajax - the second parameter should be callback function');
            return;
        }

        /**
         * Get content in current/selected language
         * @since 4.8.0
         */
        data.lang = data.lang ? data.lang : rlLang;

        // request options
        var options = {
            type: get ? 'GET' : 'POST',
            url: rlConfig['ajax_url'],
            data: data,
            dataType: 'json',
            crossDomain: true,
            xhrFields: {
                withCredentials: true
           }
        }

        // save move
        self.ajaxKey = data.ajaxKey;

        // process request
        self.ajaxRequest = $.ajax(options)
            .success(function(response, status){
                callback(response, status);
            })
            .fail(function(object, status){
                if (status == 'abort') {
                    return;
                }

                callback(false, status);
            });
    };

    /**
     * Load script(s) once
     *
     * @since 4.10.1 - asModule parameter added
     *
     * @param mixed    - script src as string or strings array
     * @param function - callback function
     * @param bool     - load script as module
     */
    this.loadScript = function(src, callback, asModule){
        var loaderClass = function(){
            let loaderClass = this;

            this.completed = false;
            this.urls = [];
            this.done = [];
            this.loaded = [];
            this.callback = function(){};

            this.init = function(src, callback, asModule){
                if (!src) {
                    console.log('loadScript Error: no scrip to load specified');
                    return;
                }

                this.urls = typeof src == 'string' ? [src] : src;
                this.callback = typeof callback == 'function'
                    ? callback
                    : this.callback;

                for (let i in this.urls) {
                    this.urls[i] = self.addRevisionToFileURL(this.urls[i]);
                }

                // Fix script url protocol
                this.fixProtocol();

                // Check for already loaded script
                this.checkLoaded();

                // Loads scripts
                for (var i in this.urls) {
                    this.load(this.urls[i], i);
                }

                // Call callback
                this.call();
            }

            this.checkLoaded = function(){
                var loaded_scripts = document.getElementsByTagName('script');

                for (var i in loaded_scripts) {
                    if (typeof loaded_scripts[i] != 'object') {
                        continue;
                    }

                    var index = this.urls.indexOf(loaded_scripts[i]['src']);

                    if (index < 0) {
                        continue;
                    }

                    // Process loaded script
                    this.processLoaded(loaded_scripts[i], index);
                }
            }

            this.processLoaded = function(script, index){
                if (script.loaded) {
                    this.loaded[index] = true;
                    this.done[index] = true;
                } else {
                    var event = script.onload;

                    script.onload = function(){
                        loaderClass.done[index] = true;

                        // Call original event
                        if (typeof event == 'function') {
                            event.call();
                        }

                        // Call new event
                        loaderClass.call();
                    };
                    this.loaded[index] = true;
                }
            }

            // Check state
            this.isStateReady = function(readyState){
                return (!readyState || $.inArray(readyState, ['loaded', 'complete', 'uninitialized']) >= 0);
            }

            // Load script
            this.load = function(url, i) {
                // Skip loaded
                if (this.loaded[i]) {
                    return;
                }

                // Create script
                var script = document.createElement('script');
                script.src = url;

                if (asModule) {
                    script.type = 'module';
                }

                // Bind to load events
                script.onload = function(){
                    if (loaderClass.isStateReady(script.readyState)) {
                        loaderClass.done[i] = true;

                        // Run the callback
                        loaderClass.call();

                        // Mark as loaded
                        script.loaded = true;
                    }

                    // Handle memory leak in IE
                    //script.onload = script.onreadystatechange = script.onerror = null; TODO TEST
                };

                // On error callback
                script.onerror = function(){
                    loaderClass.callback.call(new Error('Unable to load the script: ' + url));
                };

                // Append script into the head
                var head = document.getElementsByTagName('head')[0];
                head.appendChild(script);

                // Mark as loaded
                this.loaded[i] = true;
            }

            this.isReady = function(){
                var count = 0;
                for (var i in this.done) {
                    if (this.done[i] === true) {
                        count++;
                    }
                }

                return count == this.urls.length;
            }

            this.call = function() {
                if (this.isReady() && !this.completed) {
                    this.callback.call(this);
                    this.completed = true;
                }
            }

            this.fixProtocol = function() {
                if (!location.protocol) {
                    return;
                }

                for (var i in this.urls) {
                    if (0 === this.urls[i].indexOf('//')) {
                        this.urls[i] = location.protocol + this.urls[i];
                    }
                }
            }
        }

        var loader = new loaderClass();
        loader.init(src, callback, asModule);
    }

    this.loadStyle = function(src) {
        var loaderClass = function () {
            let loaderClass = this;

            this.completed = false;
            this.urls = [];
            this.done = [];
            this.loaded = [];

            this.init = function(src){
                if (!src) {
                    console.log('loadStyle Error: no style file to load specified');
                    return;
                }

                this.urls = typeof src == 'string' ? [src] : src;

                for (let i in this.urls) {
                    this.urls[i] = self.addRevisionToFileURL(this.urls[i]);
                }

                // Fix script url protocol
                this.fixProtocol();

                // Check for already loaded style
                this.checkLoaded();

                // Loads styles
                for (var i in this.urls) {
                    this.load(this.urls[i], i);
                }
            }

            this.checkLoaded = function() {
                var styles = document.querySelectorAll('link[rel="stylesheet"]');

                for (var i in styles) {
                    if (typeof styles[i] !== 'object' || !styles[i].href || styles[i].loaded === true) {
                        continue;
                    }

                    var index = this.urls.indexOf(styles[i]['href']);

                    if (index < 0) {
                        continue;
                    }

                    // Process load the style
                    this.processLoaded(styles[i], index);
                }
            }

            this.processLoaded = function(style, index) {
                if (style.loaded) {
                    this.loaded[index] = true;
                    this.done[index]   = true;
                } else {
                    var event = style.onload;

                    style.onload = function() {
                        loaderClass.done[index] = true;

                        // Call original event
                        if (typeof event == 'function') {
                            event.call();
                        }
                    };
                    this.loaded[index] = true;
                }
            }

            // Check state
            this.isStateReady = function(readyState){
                return (!readyState || $.inArray(readyState, ['loaded', 'complete', 'uninitialized']) >= 0);
            }

            // Load style
            this.load = function(url, i) {
                // Skip loaded
                if (this.loaded[i]) {
                    return;
                }

                // Create style
                var $style = $('<link>').attr({'rel': 'stylesheet', 'type': 'text/css', 'href': url});

                // Bind to load events
                $style.onload = function() {
                    if (loaderClass.isStateReady($style.readyState)) {
                        loaderClass.done[i] = true;

                        // Mark as loaded
                        $style.loaded = true;
                    }
                };

                // On error callback
                $style.onerror = function(){
                    loaderClass.callback.call(new Error('Unable to load the script: ' + url));
                };

                // Append style into the head
                $style.appendTo('head');

                // Mark as loaded
                this.loaded[i] = true;
            }

            this.isReady = function() {
                var count = 0;
                for (var i in this.done) {
                    if (this.done[i] === true) {
                        count++;
                    }
                }

                return count === this.urls.length;
            }

            this.fixProtocol = function() {
                if (!location.protocol) {
                    return;
                }

                for (var i in this.urls) {
                    if (0 === this.urls[i].indexOf('//')) {
                        this.urls[i] = location.protocol + this.urls[i];
                    }
                }
            }
        }

        if (rlLangDir === 'rtl'
            && src.indexOf(rlConfig.domain.replace(/^\./, '')) >= 0
            && src.indexOf('-rtl') < 0
            && src.indexOf('components/') > 0
        ) {
            src = src.replace(/(.*)\.css/, '$1-rtl.css');
        }

        new loaderClass().init(src);
    }

    /**
     * Checks if a string is an email
     *
     * @since 4.9.0
     *
     * @param email {string}
     * @returns {boolean}
     */
    this.isEmail = function (email) {
        return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(String(email).toLowerCase());
    }

    /**
     * @since 4.9.1
     * @param url
     * @return url
     */
    this.addRevisionToFileURL = function (url) {
        if (rlConfig.host && url.indexOf(rlConfig.host) >= 0) {
            url += '?rev=' + rlConfig.static_files_revision;
        }

        return url;
    }

    /**
     * Set priority z-index style to the latest active element
     *
     * @since 4.10.1
     *
     * @param Object $obj - jQuery object to set the most priority z-index style
     */
    this.setPriorityZIndex = function($obj){
        if (!$obj.length) {
            return;
        }

        var zIndex = 10;

        $('.priority-z-index').each(function(){
            if ($(this).css('z-index')) {
                var elementZIndex = parseInt($(this).css('z-index'));
                if (elementZIndex >= zIndex) {
                    zIndex = elementZIndex;
                }
            }
        });

        zIndex = zIndex + 1;
        $obj.css('z-index', zIndex);
        $('.priority-z-index__parent').css('z-index', zIndex);
    }
}

var flUtil = new flUtilClass();
