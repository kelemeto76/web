import Vue from 'vue';

import 'libs/filters';
import 'config/config';

import 'Template/Layout/style.scss';

import { BELoader } from 'libs/bedita';

import ModulesIndex from 'app/pages/modules/index';
import ModulesView from 'app/pages/modules/view';
import TrashIndex from 'app/pages/trash/index';
import TrashView from 'app/pages/trash/view';
import ImportView from 'app/pages/import/index';
import FilterBoxView from 'app/components/filter-box';
import RelationsAdd from 'app/components/relation-view/relations-add';
import EditRelationParams from 'app/components/edit-relation-params';

import datepicker from 'app/directives/datepicker';
import jsoneditor from 'app/directives/jsoneditor';
import richeditor from 'app/directives/richeditor';
import VueHotkey from 'v-hotkey';

import sleep from 'sleep-promise';

const _vueInstance = new Vue({
    el: 'main',

    components: {
        ModulesIndex,
        ModulesView,
        TrashIndex,
        TrashView,
        ImportView,
        RelationsAdd,
        FilterBoxView,
        EditRelationParams,
    },

    data() {
        return {
            vueLoaded: false,
            urlPagination: '',
            searchQuery: '',
            pageSize: '',
            page: '',
            sort: '',
            panelIsOpen: false,
            panelAction: null,
            panelData: null,
            addRelation: {},
            editingRelationParams: null,

            urlFilterQuery: {
                q: '',
                filter: {},
            },

            pagination: {
                page: '',
                page_size: '',
            }
        }
    },

    /**
     * properties or methods available for injection into its descendants
     * (inject: ['property'])
     */
    provide() {
        return {
            requestPanel: (...args) => this.requestPanel(...args),
            closePanel: (...args) => this.closePanel(...args),
            returnDataFromPanel: (...args) => this.returnDataFromPanel(...args),
        }
    },

    /**
     * setup Vue instance before creation
     *
     * @return {void}
     */
    beforeCreate() {
        // Register directives
        Vue.use(jsoneditor);
        Vue.use(datepicker);
        Vue.use(richeditor);
        Vue.use(VueHotkey);

        // load BEplugins's components
        BELoader.loadBeditaPlugins();
    },

    created() {
        this.vueLoaded = true;

        // load url params when component initialized
        this.loadUrlParams();
    },

    watch: {
        panelIsOpen(value) {
            var cl = document.querySelector('html').classList;
            if (value) {
                cl.add('is-clipped');
            } else {
                cl.remove('is-clipped');
            }
        },

        /**
         * watch pageSize variable and update pagination.page_size accordingly
         *
         * @param {Number} value page size number
         */
        pageSize(value) {
            this.pagination.page_size = value;
        }
    },

    mounted: function () {
        this.$nextTick(function () {
            if(BEDITA.template == 'view') {
                this.alertBeforePageUnload();
            }
        })
    },

    methods: {
        // Events Listeners

        /**
         * listen to FilterBoxView event filter-objects
         *
         * @param {Object} filter
         *
         * @return {void}
         */
        onFilterObjects(filter) {
            this.urlFilterQuery = filter;
            this.page = '';
            this.page = '';
            this.page = '';

            this.applyFilters(this.urlFilterQuery);
        },

        /**
         * listen to FilterBoxView event filter-update-page-size
         *
         * @param {Number} pageSize
         *
         * @return {void}
         */
        onUpdatePageSize(pageSize) {
            this.pageSize = pageSize;
            this.page = '';
            this.applyFilters(this.urlFilterQuery);
        },

        /**
         * listen to FilterBoxView event filter-update-current-page
         *
         * @param {Number} page
         *
         * @return {void}
         */
        onUpdateCurrentPage(page) {
            this.page = page;
            this.applyFilters(this.urlFilterQuery);
        },

        /**
         * on page click:
         * - if panel is open, close it and stop event propagation
         * - if panel is closed do nothing
         *
         * @return {void}
         */
        pageClick(event) {
            // temporary comment: we do not want that panel is closed, when it contains pagination...
            // if (this.panelIsOpen) {
            //     this.closePanel();
            //     event.preventDefault();
            //     event.stopPropagation();
            // }
        },

        /**
         * return data from panel
         *
         * @param {Object} data
         *
         * @return {void}
         */
        returnDataFromPanel(data) {
            this.closePanel();

            // return data from RelationsAdd view component
            if (data.action === 'add-relation') {
                this.$refs["moduleView"]
                    .$refs[data.relationName]
                    .$refs["relation"].appendRelations(data.objects);
            }

            // return data from EditRelationParams view component
            if (data.action === 'edit-params') {
                const relationName = data.item.name;
                this.$refs["moduleView"]
                    .$refs[relationName]
                    .$refs["relation"].updateRelationParams(data.item);
            }
        },

        /**
         * close panel and clear data
         *
         * @return {void}
         */
        closePanel() {
            this.panelIsOpen = false;
            this.addRelation = {
                name: '',
                alreadyInView: [],
            };

            this.editingRelationParams = null;
        },

        /**
         * request panel and pass data
         *
         * @param {Object} data
         */
        requestPanel(request) {
            this.panelIsOpen = true;
            this.panelAction = request.action;
            this.panelData = request.data;

            // open panel for relations add
            if(request.relation && request.relation.name) {
                this.addRelation = request.relation;
            } else if (request.editRelationParams && request.editRelationParams.name) {
                this.editingRelationParams = request.editRelationParams;
            }
        },

        /**
         * extract params from page url
         *
         * @returns {void}
         */
        loadUrlParams() {
            // look for query string params in window url
            if (window.location.search) {
                const urlParams = window.location.search;

                // search for q='some string' both after ? and & tokens
                const queryStringExp = /[?&]q=([^&#]*)/g;
                let matches = urlParams.match(queryStringExp);
                if (matches && matches.length) {
                    matches = matches.map(e => e.replace(queryStringExp, '$1'));
                    this.urlFilterQuery.q = matches[0];
                }

                // search for filter['filter_name']='filter_value' both after ? and & tokens
                const filterExp = /[?&]filter\[(.*?)\]=([^&#]*)/g;
                matches = filterExp.exec(urlParams);
                if (matches && matches.length === 3) {
                    const filterKey = matches[1];
                    const filterValue = matches[2]
                    this.urlFilterQuery.filter[filterKey] = filterValue;
                }

                // search for page_size='some string' both after ? and & tokens
                const pageSizeExp = /[?&]page_size=([^&#]*)/g;
                matches = urlParams.match(pageSizeExp);
                if (matches && matches.length) {
                    matches = matches.map(e => e.replace(pageSizeExp, '$1'));
                    this.pageSize = this.isNumeric(matches[0]) ? matches[0] : '';
                }

                // search for page='some string' both after ? and & tokens
                const pageExp = /[?&]page=([^&#]*)/g;
                matches = urlParams.match(pageExp);
                if (matches && matches.length) {
                    matches = matches.map(e => e.replace(pageExp, '$1'));
                    this.page = this.isNumeric(matches[0]) ? matches[0] : '';
                }

                // search for sort='some string' both after ? and & tokens
                const sortExp = /[?&]sort=([^&#]*)/g;
                matches = urlParams.match(sortExp);
                if (matches && matches.length) {
                    matches = matches.map(e => e.replace(sortExp, '$1'));
                    this.sort = matches[0];
                }
            }
        },

        /**
         * build coherent url based on these params:
         * - q= query string
         * - page_size
         *
         * @param {Object} params
         * @returns {String} url
         */
        buildUrlParams(params) {
            let url = `${window.location.origin}${window.location.pathname}`;
            let first = true;
            let queryId = '?';
            const separator = '&';

            Object.keys(params).forEach((key) =>  {
                if (params[key] && params[key] !== '') {
                    const query = params[key];
                    let entry = `${key}=${query}`;

                    // parse filter property
                    if (key === 'filter') {
                        let filter = '';
                        Object.keys(query).forEach((filterKey) => {
                            if (query[filterKey] !== '') {
                                filter += `filter[${filterKey}]=${query[filterKey]}`;
                            }
                        });

                        entry = filter;
                    }

                    url += `${first ? queryId : separator}${entry}`;
                    first = false;
                }
            });

            return url;
        },

        /**
         * reset queryString in search keeping pagination options
         *
         * @returns {void}
         */
        resetFilters() {
            this.page = '';
            this.pageSize = '';
            let filter = {
                q: '',
            }
            this.applyFilters(filter);
        },

        /**
         * apply page filters such as query string or pagination
         *
         * @params {Object} filters filters object
         *
         * @returns {void}
         */
        applyFilters(filters) {
            let url = this.buildUrlParams({
                q: filters.q,
                filter: filters.filter,
                page_size: this.pageSize,
                page: this.page,
                sort: this.sort,
            });
            window.location.replace(url);
        },

        /**
         * alerts onbeforeunload if forms changed and it's not a submit
         *
         * @returns {void}
         */
        alertBeforePageUnload() {
            var forms = [...document.querySelectorAll('form')];
            forms.forEach((form) => {
                form.addEventListener('change', (ev) => {
                    form.changed = true;
                });
                form.addEventListener('submit', (ev) => {
                    if (form.action.endsWith('/delete')) {
                        if (!confirm("Do you really want to trash the object?")) {
                            ev.preventDefault();
                            return;
                        }
                    }
                    form.submitting = true;
                });
            });

            window.onbeforeunload = function() {
                if (forms.some((f) => f.changed) && !forms.some((f) => f.submitting)) {
                    return "There are unsaved changes, are you sure you want to leave page?";
                }
            }
        },

        /**
         * Helper function
         *
         * @param {String|Number} num
         * @returns {Boolean}
         */
        isNumeric(num) {
            return !isNaN(num);
        },
    }
});

window._vueInstance = _vueInstance;
