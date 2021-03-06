var Oro = Oro || {};
Oro.Datagrid = Oro.Datagrid || {};

/**
 * Basic grid class.
 *
 * Triggers events:
 *  - "cellEdited" when one of cell of grid body row is edited
 *  - "rowClicked" when row of grid body is clicked
 *
 * @class   Oro.Datagrid.Grid
 * @extends Backgrid.Grid
 */
Oro.Datagrid.Grid = Backgrid.Grid.extend({
    /** @property */
    tagName: 'div',

    /** @property */
    requestsCount: 0,

    /** @property */
    className: 'clearfix',

    /** @property */
    template: _.template(
        '<div class="toolbar"></div>' +
        '<div class="container-fluid">' +
            '<div class="grid-container">' +
                '<table class="grid table-hover table table-bordered table-condensed"></table>' +
                '<div class="no-data"></div>' +
                '<div class="loading-mask"></div>' +
            '</div>' +
        '</div>'
    ),

    /** @property */
    noDataTemplate: _.template('<span><%= hint %><span>'),

    /** @property {Object} */
    selectors: {
        grid:        '.grid',
        toolbar:     '.toolbar',
        noDataBlock: '.no-data',
        loadingMask: '.loading-mask'
    },

    /** @property {Oro.Datagrid.Header} */
    header: Oro.Datagrid.Header,

    /** @property {Oro.Datagrid.Body} */
    body: Oro.Datagrid.Body,

    /** @property {Oro.Datagrid.Toolbar} */
    toolbar: Oro.Datagrid.Toolbar,

    /** @property {Oro.LoadingMask} */
    loadingMask: Oro.LoadingMask,

    /** @property {Oro.Datagrid.Action.Column} */
    actionsColumn: Oro.Datagrid.Action.Column,

    /**
     * @property {Object} Default properties values
     */
    defaults: {
        noDataHint: 'No data found.',
        rowClickActionClass: 'row-click-action',
        rowClassName: '',
        toolbarOptions: {},
        addResetAction: true,
        addRefreshAction: true,
        rowClickAction: undefined,
        rowActions: []
    },

    /**
     * Initialize grid
     *
     * @param {Object} options
     * @param {Backbone.Collection} options.collection
     * @param {Backbone.Collection|Array} options.columns
     * @param {String} [options.noDataHint] Text which displayed when datagrid collection is empty
     * @param {String} [options.rowClickActionClass] CSS class for row with click action
     * @param {String} [options.rowClassName] CSS class for row
     * @param {Object} [options.toolbarOptions] Options for toolbar
     * @param {Boolean} [options.addResetAction] If TRUE reset action will be added in toolbar
     * @param {Boolean} [options.addRefreshAction] If TRUE refresh action will be added in toolbar
     * @param {Oro.Datagrid.Action.AbstractAction[]} [options.rowActions] Array of row actions prototypes
     * @param {Oro.Datagrid.Action.AbstractAction} [options.rowClickAction] Prototype for action that handles row click
     * @throws {TypeError} If mandatory options are undefined
     */
    initialize: function(options) {
        options = options || {};

        // Check required options
        if (!options.collection) {
            throw new TypeError("'collection' is required")
        }
        this.collection = options.collection;

        if (!options.columns) {
            throw new TypeError("'columns' is required")
        }

        // Init properties values based on options and defaults
        _.extend(this, this.defaults, options);

        this._initRowActions();

        if (this.rowClickAction) {
            // This option property is used in Oro.Datagrid.Body
            options.rowClassName = this.rowClickActionClass + ' ' + this.rowClassName;
        }

        options.columns.push(this._createActionsColumn());

        this.loadingMask = this._createLoadingMask();
        this.toolbar = this._createToolbar(this.toolbarOptions);

        Backgrid.Grid.prototype.initialize.apply(this, arguments);

        // Listen and proxy events
        this._listenToCollectionEvents();
        this._listenToBodyEvents();
    },

    /**
     * Inits this.rowActions and this.rowClickAction
     *
     * @private
     */
    _initRowActions: function() {
        var rowClickActions = _.filter(this.rowActions, function(action, key) {
            if (action.prototype.runOnRowClick) {
                delete this.rowActions[key];
                return true;
            }
            return false;
        }, this);

        if (rowClickActions.length && !this.rowClickAction) {
            this.rowClickAction = rowClickActions[0];
        }
    },

    /**
     * Creates actions column
     *
     * @return {Backgrid.Column}
     * @private
     */
    _createActionsColumn: function() {
        return new this.actionsColumn({
            actions: this.rowActions
        });
    },

    /**
     * Creates loading mask
     *
     * @return {Oro.LoadingMask}
     * @private
     */
    _createLoadingMask: function() {
        return new this.loadingMask();
    },

    /**
     * Creates instance of toolbar
     *
     * @param {Object} toolbarOptions
     * @return {Oro.Datagrid.Toolbar}
     * @private
     */
    _createToolbar: function(toolbarOptions) {
        return new this.toolbar(_.extend({}, toolbarOptions, {
            collection: this.collection,
            actions: this._getToolbarActions()
        }));
    },

    /**
     * Get actions of toolbar
     *
     * @return {Array}
     * @private
     */
    _getToolbarActions: function() {
        var result = [];
        if (this.addRefreshAction) {
            result.push(this.getRefreshAction());
        }
        if (this.addResetAction) {
            result.push(this.getResetAction());
        }
        return result;
    },

    /**
     * Get action that refreshes grid's collection
     *
     * @return Oro.Datagrid.Action.RefreshCollectionAction
     */
    getRefreshAction: function() {
        if (!this.refreshAction) {
            this.refreshAction = new Oro.Datagrid.Action.RefreshCollectionAction({
                collection: this.collection,
                launcherOptions: {
                    label: 'Refresh',
                    className: 'btn',
                    iconClassName: 'icon-refresh'
                }
            });
        }
        return this.refreshAction;
    },

    /**
     * Get action that resets grid's collection
     *
     * @return Oro.Datagrid.Action.ResetCollectionAction
     */
    getResetAction: function() {
        if (!this.resetAction) {
            this.resetAction = new Oro.Datagrid.Action.ResetCollectionAction({
                collection: this.collection,
                launcherOptions: {
                    label: 'Reset',
                    className: 'btn',
                    iconClassName: 'icon-repeat'
                }
            });
        }
        return this.resetAction;
    },

    /**
     * Listen to events of collection
     *
     * @private
     */
    _listenToCollectionEvents: function() {
        this.collection.on('request', function(model, xhr, options) {
            this._beforeRequest();
            var self = this;
            var always = xhr.always;
            xhr.always = function() {
                always.apply(this, arguments);
                self._afterRequest();
            }
        }, this);

        this.collection.on('remove', this._onRemove, this);
    },

    /**
     * Listen to events of body, proxies events "rowClicked" and "rowEdited", handle run of rowClickAction if required
     *
     * @private
     */
    _listenToBodyEvents: function() {
        this.listenTo(this.body, 'rowClicked', function(row) {
            this.trigger('rowClicked', this, row);
            this._runRowClickAction(row);
        });
        this.listenTo(this.body, 'cellEdited', function(row, cell) {
            this.trigger('cellEdited', this, row, cell);
        });
    },

    /**
     * Create row click action
     *
     * @param {Oro.Datagrid.Row} row
     * @private
     */
    _runRowClickAction: function(row) {
        if (this.rowClickAction) {
            var action = new this.rowClickAction({
                model: row.model
            });
            action.run();
        }
    },

    /**
     * Renders the grid, no data block and loading mask
     *
     * @return {*}
     */
    render: function () {
        this.$el.empty();

        this.$el = this.$el.append($(this.template()));

        this.renderToolbar();
        this.renderGrid();
        this.renderNoDataBlock();
        this.renderLoadingMask();

        /**
         * Backbone event. Fired when the grid has been successfully rendered.
         * @event rendered
         */
        this.trigger("rendered");

        return this;
    },

    /**
     * Renders the grid's header, then footer, then finally the body.
     */
    renderGrid: function() {
        var $el = this.$(this.selectors.grid);

        $el.append(this.header.render().$el);
        if (this.footer) {
            $el.append(this.footer.render().$el);
        }
        $el.append(this.body.render().$el);
    },

    /**
     * Renders grid toolbar.
     */
    renderToolbar: function() {
        this.$(this.selectors.toolbar).append(this.toolbar.render().$el);
    },

    /**
     * Renders loading mask.
     */
    renderLoadingMask: function() {
        this.$(this.selectors.loadingMask).append(this.loadingMask.render().$el);
        this.loadingMask.hide();
    },

    /**
     * Render no data block.
     */
    renderNoDataBlock: function() {
        this.$(this.selectors.noDataBlock).append($(this.noDataTemplate({
            hint: this.noDataHint.replace('\n', '<br />')
        }))).hide();
        this._updateNoDataBlock();
    },

    /**
     * Triggers when collection "request" event fired
     *
     * @private
     */
    _beforeRequest: function() {
        this.requestsCount++;
        this.loadingMask.show();
        this.toolbar.disable();
    },

    /**
     * Triggers when collection request is done
     *
     * @private
     */
    _afterRequest: function() {
        this.requestsCount--;
        if (this.requestsCount == 0) {
            this.loadingMask.hide();
            this.toolbar.enable();
            this._updateNoDataBlock();
            /**
             * Backbone event. Fired when data for grid has been successfully rendered.
             * @event grid_load:complete
             */
            Oro.Events.trigger("grid_load:complete", this.collection);
        }
    },

    /**
     * Update no data block status
     *
     * @private
     */
    _updateNoDataBlock: function() {
        if (this.collection.models.length > 0) {
            this.$(this.selectors.grid).show();
            this.$(this.selectors.noDataBlock).hide();
        } else {
            this.$(this.selectors.grid).hide();
            this.$(this.selectors.noDataBlock).show();
        }
    },

    /**
     * Triggers when collection "remove" event fired
     *
     * @private
     */
    _onRemove: function() {
        this.collection.fetch();
    },

    /**
     * Set additional parameter to send on server
     *
     * @param {String} name
     * @param value
     */
    setAdditionalParameter: function(name, value) {
        var state = this.collection.state;
        if (!_.has(state, 'parameters')) {
            state.parameters = {};
        }

        state.parameters[name] = value;
    }
});
