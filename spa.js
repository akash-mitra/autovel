/**
 * @author: akashmitra@gmail.com
 * Spartacus - Single Page Application Java Script plugin.
 * Allows you to create single page CRUD application
 * based on AJAX based server response(s)
 */

(function($){
    $.spartacus = function(el, resources, options){
        // To avoid scope issues, use 'plugin' instead of 'this'
        // to reference this class from internal events and functions.
        var plugin = this;
        
        // Access to jQuery and DOM versions of element
        plugin.$el = $(el);
        plugin.el = el;
        
        // Add a reverse reference to the DOM object
        plugin.$el.data("spartacus", plugin);


        var getTableHeader = function (cols)
        {
            var html = '';
            for (var i=0; i < cols.length; i++)
                html += '<th>' + cols[i].toUpperCase() + '</th>';
            return '<thead><tr>' + html + '</tr></thead>';
        }

        var getTableRows = function (data, cols, showPageAnchors)
        {
            var html = '';
            for (var i=0; i< data.length; i++) {
                html += '<tr>';
                for (var j=0; j< cols.length; j++) {
                    if (showPageAnchors.indexOf(cols[j]) >= 0)
                        html += '<td><a href="#" class="showmodal" data-id="' + data[i]["id"] + '">' 
                            + data[i][cols[j]] 
                            + '</a></td>';
                    else 
                        html += '<td>' + data[i][cols[j]] + '</td>';
                }
                html += '</tr>';
            }
            return html;
        }

        var buildTable = function (header, rows, classes)
        {
            if (typeof classes === 'undefined') classes = 'table';
            return '<table class="' + classes + '">' 
                    + header 
                    + '<tbody>' + rows + '</tbody>'
                    + '</table>';
        }

        var showTable = function (data, options)
        {
            if (options.hasOwnProperty("exclude")) col_exclude_list = options.exclude;
            else col_exclude_list = [];

            var cols = Object.keys(data[0]);
            cols =  cols.filter(function (element) {
                return col_exclude_list.indexOf(element) < 0;
            });
            var tableHeader = getTableHeader(cols);
            var tableRows = getTableRows (data, cols, options.showPageAnchors);
            var table = buildTable (tableHeader, tableRows, 'table table-sm');
            plugin.$el.html(table);
        }


        var setModalButtons = function (modal_type)
        {
            if (modal_type === 'show') {
                if (plugin.options.enableEdit === true) {
                    $('#btnPrimaryAction')
                        .data("operation", "edit")
                        .prop('disabled', false)
                        .text(plugin.options.editBtnText)
                        .show();
                }
                else {
                    $('#btnPrimaryAction').hide();   
                }
                $('#btnSecondaryAction').hide();
                $('#btnClose').show();
            }

            if (modal_type === 'create') {
                $('#btnPrimaryAction')
                    .data("operation", "post")
                    .prop('disabled', false)
                    .text(plugin.options.saveBtnText)
                    .show();
                $('#btnSecondaryAction').hide();
                $('#btnClose').show();
            }

            if (modal_type === 'edit') 
            {
                $('#btnPrimaryAction')
                    .data("operation", "patch")
                    .prop('disabled', false)
                    .text(plugin.options.saveBtnText)
                    .show();

                if (plugin.options.enableDelete === true) {
                    //console.log('enabling delete');
                    $('#btnSecondaryAction')
                        .data("operation", "delete")
                        .prop('disabled', false)
                        .text("Delete")
                        .show();
                }
                else {
                    //console.log('disabling secondary button');
                    $('#btnSecondaryAction').hide();
                }
                $('#btnClose').show();
            }
        } // setModalButtons


        /**
         * Based on the data type and the size of the attribute
         * it returns the proper HTML input control
         */
        var getFormControlHTML = function (name, data_type, size, value)
        {
            if (typeof value === 'undefined' || (! value)) value = '';
            var control_type = getSuitableFormControlType (data_type, size);

            var html = '';
            if (control_type === 'text' || control_type === 'number' || control_type === 'date') 
            {
                html = '<div class="form-group">' 
                + '<label for="Input' + name + '">' + name.toUpperCase() +'</label>' 
                + '<input type="' + control_type + '" class="form-control" '
                + 'id="Input' + name + '" '
                + 'name="' + name + '" '
                + 'value="' + value + '" '
                + 'placeholder="Enter ' + name + '">'
                + '</div>';
            }

            if (control_type === 'textarea') 
            {
                html = '<div class="form-group">' 
                + '<label for="InputArea' + name + '">' + name.toUpperCase() +'</label>' 
                + '<textarea rows="3" class="form-control" '
                + 'name="' + name + '" '
                + 'id="InputArea' + name + '">'
                + value + '</textarea>'
                + '</div>';
            }
            return html;
        }


        /**
         * Retrives the resource in JSON form from
         * server and calls the "success" function
         * with the retrieved JSON data
         */
        var getResource = function (id, success)
        {
            $.ajax({
              url: '/' + plugin.resources + '/' + id,
              data: { "contentType": "JSON" },
              success: success,
              dataType: 'json'
            });   
        }


        /**
         * Constructs the URL based on the given action
         */
        var getRoute = function (action)
        {
            var suffix = '/';
            
            if (action === 'patch' || action === 'delete')
                suffix += modalkey();
            
            return '/' + resources + suffix; 
        }


        /**
         * Sets or retrives the resource key in or from 
         * the modal window
         */
        var modalkey = function (id) { 
            if (typeof id === 'undefined')
                return $('#id').val();
            $('#id').val(id); 
        }
        

        var fillCreateModal = function ()
        {
            setModalButtons ('create');
            displayDataEntryForm ();
        }

        var fillEditModal = function (id)
        {
            $('#spaModalBody').slideUp(plugin.options.duration);
            getResource (id, function (data) {
                displayDataEntryForm (data);
                setModalButtons ('edit');
                $('#spaModalBody').slideDown(plugin.options.duration);
            });
        }

        var fillShowModal = function (id)
        {
            setModalButtons ('show');
            modalkey (id);
            $('#spaModalBody').slideUp(plugin.options.duration);
            var _fillShowModal = function (data) {
                var keys = Object.keys(data);
                var rows = '';
                for (var i=0; i< keys.length; i++)
                    rows += '<tr><td>' + keys[i].toUpperCase() + '</td><td>' + data[keys[i]] + '</td></tr>';
                var table = buildTable('<thead><tr><th>Property</th><th>Value</th></thead>', rows);
                $('#spaModalLabel').text (data[plugin.options.resourceName]);
                $('#spaModalBody').html (table).slideDown(plugin.options.duration);
            };

            getResource (id, _fillShowModal);
        }
        
        var fillIndexTable = function (data)
        {
            showTable(data, {
                "exclude": plugin.options.exclusions,
                "showPageAnchors": (plugin.options.enableShow ? plugin.options.showPageAnchors : []),
            });
        }
        var displayIndexTable = function ()
        {
            $.ajax({
              url: '/' + plugin.resources,
              data: { "contentType": "JSON" },
              success: fillIndexTable,
              dataType: 'json'
            });
        }

        var enableShow = function ()
        {
            $('body').on('click',  'a.showmodal', function () {   
                $('#spaModalLabel').text("Resource View");
                $('#spaModalBody').text("Fetching Details...");
                fillShowModal ($(this).data("id"));
                $('#spaModal').modal();
            });
        }


        var enableCreate = function ()
        {
            $('#btnNewResource')
                .removeClass('hide')
                .text(plugin.options.createBtnText)
                .click (function () {
                    $('#spaModalLabel').text(plugin.options.createBtnText);
                    fillCreateModal ();
                    $('#spaModal').modal();
                });
        }

        /**/
        var enableEdit = function ()
        {
            $('#btnSecondaryAction').show();
        }


        /**
         * Returns the suitable input control based 
         * on datatype and size. 
         */
        var getSuitableFormControlType = function (datatype, size)
        {
            if (datatype === "increments") return null;
            if (datatype === "double") return 'number';
            if (datatype === "string" && size <= 64) return 'text';
            if (datatype === "string" && size > 64) return 'textarea';
            if (datatype === "date") return 'date';
            return 'text';
        }


        /**
         * Builds the actual data entry input controls
         */ 
        var buildDataEntryForm = function (metadata, data, holderId)
        {
            if (typeof data === 'undefined') data = [];

            var jsonData = JSON.parse(metadata);
            var formInnerHTML = '';
            jsonData.forEach (function (e) {
                var size = e.hasOwnProperty('size')? e.size : 0;
                var value = data[e.name];
                formInnerHTML += getFormControlHTML (e.name, e.datatype, size, value);;
            });
            $(holderId).html ('<form id="newEntry">' + formInnerHTML + '</form>');
        }


        /**
         * Gets the metadata information from server and 
         * based on the metadata, shows a data entry form.
         * If "prefill" information is provided, then also
         * fills the inputs with prefill data
         */
        var displayDataEntryForm = function (prefill)
        {
            $.get('/metadata/' + plugin.resources, function (metadata) {
                buildDataEntryForm (metadata, prefill, '#spaModalBody');
            });
        }


        // remove records from arrays except the record
        // that contains Id attribute
        var getId = function (e) {
            return e.name === 'id';
        }


        // Generic AJAX based form submitter for Laravel routes
        var submitForm = function (action, url, data, success, error)
        {
            if (typeof (success) === 'undefined') success = function (msg) {
                displayIndexTable ();
                $('#spaModalBody').html ('<p>' + action[0].toUpperCase() + action.slice(1) + ' action has been completed Successfully!</p>');
                $('#btnPrimaryAction').hide();
                $('#btnSecondaryAction').hide();
            }

            if (typeof (error) === 'undefined') error = function (msg) {
                $('#spaModalBody').html ('<p>' + action[0].toUpperCase() + action.slice(1) + ' action encountered an error!</p>');
                $('#btnPrimaryAction').hide();
                $('#btnSecondaryAction').hide();
            }

            data["_method"] = action.toUpperCase();
            
            $.ajax ({
                "url": url, 
                "type": action, 
                "data": data, 
                "success": success, 
                "error": error,
                "headers": {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }
        
        
        /**
         * Submits the data from any Form in the modal to the 
         * supplied action
         */
        var formAction = function (action)
        {
            // get form data from the form
            var data = $('#spaModalBody').find('form').serializeArray();
            // detect the remote route
            var route = getRoute(action);
            // send the data to route
            submitForm (action, route, data);
        }


        /// Intercept the Modal Action button clicks,
        // determine the course of action and delegate
        var modalActionRouter = function ()
        {
            // disable the clicked button
            $(this).prop('disabled', true);

            // detect the action - e.g. create new, save (update), edit, delete etc.
            var action = $(this).data("operation");
            
            // button actions
            if (action === 'post' || action === 'patch' || action === 'delete') formAction (action);
            if (action === 'edit' ) fillEditModal (modalkey()); 
        }

        /* PUBLIC FUNCTION */
        plugin.init = function(){
            if( typeof( resources ) === "undefined" || resources === null ) {
                throw new Error('Resource not defined for spa() function call');
            }
            plugin.resources = resources;
            plugin.options = $.extend({},$.spartacus.defaultOptions, options);
            
            // Put your initialization code here
            displayIndexTable ();

            if (plugin.options.enableShow) enableShow ();
            if (plugin.options.enableCreate) enableCreate ();
            
            // enable the primary/secondary action buttons in the modal
            $('#btnPrimaryAction').click (modalActionRouter);
            $('#btnSecondaryAction').click (modalActionRouter)
                .hide(); // hide by default
        };
        
        
        // Run initializer
        plugin.init();
    };
    
    $.spartacus.defaultOptions = {

        // coloumns to exclude
        "exclusions": ['id', 'created_at', 'updated_at', 'user_id'],

        // show page related properties
        "enableShow": true,
        "showPageAnchors": ['name'],
        
        // edit page related 
        "enableEdit": true,

        // new page related 
        "enableCreate": true,

        // enable delete
        "enableDelete": true,

        // resource name identifying columns
        "resourceName": "name",

        // button labels
        "editBtnText": "Edit",
        "deleteBtnText": "Delete",
        "createBtnText": "Create New",
        "saveBtnText": "Save",

        // animation duration
        "duration": 200
    };


    
    $.fn.spartacus = function(resources, options){
        return this.each(function(){
            (new $.spartacus(this, resources, options));
		   // HAVE PLUGIN DO STUFF HERE

		   // END DOING STUFF
        });
    };
})(jQuery);