/**
 * Filter Box View component
 *
 * allows to filter a list of objects with a text field, properties and a pagination toolbar
 *
 * <filter-box-view> component
 *
 * @prop {String} configPaginateSizes
 * @prop {Boolean} filterActive Some filter is active on currently displayed data
 * @prop {Array} filterList custom filters to show
 * @prop {Object} initFilter
 * @prop {String} objectsLabel
 * @prop {Object} pagination
 * @prop {String} placeholder
 * @prop {Object} relationTypes relation types available for relation (left/right)
 * @prop {Array} selectedTypes Types selected
 */

import { DEFAULT_PAGINATION, DEFAULT_FILTER } from 'app/mixins/paginated-content';
import merge from 'deepmerge';
import { t } from 'ttag';
import { warning } from 'app/components/dialog/dialog';

export default {
    components: {
        InputDynamicAttributes: () => import(/* webpackChunkName: "input-dynamic-attributes" */'app/components/input-dynamic-attributes'),
        CategoryPicker: () => import(/* webpackChunkName: "category-picker" */'app/components/category-picker/category-picker'),
        FolderPicker: () => import(/* webpackChunkName: "folder-picker" */'app/components/folder-picker/folder-picker'),
    },

    props: {
        configPaginateSizes: {
            type: String,
            default: "[10]"
        },
        filterActive: Boolean,
        filterList: {
            type: Array,
            default: () => [],
        },
        initFilter: {
            type: Object,
            default: DEFAULT_FILTER,
        },
        objectsLabel: {
            type: String,
            default: t`Objects`
        },
        pagination: {
            type: Object,
            default: () => DEFAULT_PAGINATION
        },
        placeholder: {
            type: String,
            default: t`Search`
        },
        relationTypes: {
            type: Object
        },
        selectedTypes: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            dynamicFilters: {},
            /**
             * Enable position filter by descendants.
             * When disabled, only direct children are fetched.
             * This will switch the filter between `parent` and `ancestor`.
             */
            filterByDescendants: false,
            moreFilters: this.filterActive,
            pageSize: this.pagination.page_size,
            queryFilter: {},
            selectedStatuses: [],
            statusFilter: {},
            timer: null,
        };
    },

    created() {
        // merge default filters with initFilter
        let mergeFilters = this.formatFilters();
        this.queryFilter = merge.all([
            DEFAULT_FILTER,
            this.queryFilter,
            mergeFilters,
            this.initFilter
        ]);

        // remove custom filters from the list of dynamic filters
        this.dynamicFilters = this.filterList.filter(f => {
            if (f.name == 'status') {
                this.statusFilter = f;

                return false;
            }

            return true;
        });

        this.selectedStatuses = Object.values(this.initFilter?.filter?.status || {});
        this.filterByDescendants = !!this.initFilter.filter?.ancestor;
    },

    computed: {
        paginateSizes() {
            return JSON.parse(this.configPaginateSizes);
        },

        /**
         * get relation's right object types
         *
         * @returns {Array} array of object types
         */
        rightTypes() {
            return (this.relationTypes && this.relationTypes.right) || [];
        },

        /**
         * check which navigation layout needs to be rendered
         *
         * @return {void}
         */
        isFullPaginationLayout() {
            return this.pagination.page_count > 1 && this.pagination.page_count <= 7;
        },

        /**
         * Returns the list of categories used to filter data
         * converted by object to array.
         * @returns {Array}
         */
        initCategories() {
            return Object.values(this.initFilter?.filter?.categories || {});
        },

        initFolder() {
            return this.initFilter.filter[this.positionFilterName];
        },

        positionFilterName() {
            return this.filterByDescendants ? 'ancestor' : 'parent';
        }
    },

    watch: {
        /**
         * watch initFilter and assign it to queryFilter
         *
         * @param {Object} value filter object
         *
         * @returns {void}
         */
        initFilter(value) {
            this.queryFilter = merge(this.queryFilter, value);
        },

        /**
         * watcher for pageSize variable, change pageSize and reload relations
         *
         * @emits Event#filter-update-page-size
         *
         * @returns {void}
         */
        pageSize() {
            this.$emit("filter-update-page-size", this.pageSize);
        },

        selectedTypes(value) {
            this.queryFilter.filter.type = value;
        },

        /**
         * Add selected statuses to the query filters.
         * @param {String[]} value Selected statuses list
         */
        selectedStatuses(value) {
            this.queryFilter.filter.status = value;
        },
    },

    methods: {
        /**
         * Return translation of ucfirst label objectsLabel.
         *
         * @returns {String}
         */
        label() {
            if (!this.objectsLabel) {
                return t`Items`;
            }

            return this.ucfirst(this.objectsLabel);
        },

        /**
         * First char upper case for string.
         * @param {String} str The string
         * @return {String}
         */
        ucfirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        },

        /**
         * trigger filter-objects event when query string has 3 or more carachter
         *
         * @emits Event#filter-objects
         */
        onQueryStringKeyup() {
            const queryString = this.queryFilter.q || "";

            clearTimeout(this.timer);
            if (queryString.trim().length >= 3 || queryString.trim().length === 0) {
                this.timer = setTimeout(() => {
                    this.$emit("filter-objects", this.queryFilter);
                }, 300);
            }
        },

        /**
         * Handle Search input when value changes
         * If search text is empty or at least 3 characters long, ok.
         * Otherwise, warn that search text is too short.
         */
        onQueryStringChange() {
            if (!this.searchFieldValid()) {
                this.searchFieldDialog();
            }
        },

        /**
         * Verify search text.
         * If is empty or more than 2 characters long, return true.
         * Return false otherwise.
         */
        searchFieldValid() {
            this.queryFilter.q = this.queryFilter.q.trim();

            return (this.queryFilter.q.length === 0 || this.queryFilter.q.length > 2);
        },

        /**
         * Verify search text.
         * If is empty or more than 2 characters long, ok.
         * Show prompt dialog otherwise.
         */
        searchFieldDialog() {
            warning(t`Search text too short. Minimum length is 3. Retry`);
        },

        onOtherFiltersChange() {
            this.$emit("filter-objects", this.queryFilter);
        },

        /**
         * load custom filters property names
         *
         * @returns {Object} filters' name
         */
        formatFilters() {
            let filter = {};
            this.filterList.forEach (
                f => (filter[f.name] = f.date ? {} : "")
            );

            return { filter };
        },

        /**
         * apply filters
         *
         * @emits Event#filter-objects-submit
         */
        applyFilter() {
            if (this.searchFieldValid()) {
                this.$emit("filter-objects-submit", this.queryFilter);

                return;
            }
            this.searchFieldDialog();
        },

        /**
         * reset filters
         *
         * @emits Event#filter-reset
         */
        resetFilter() {
            this.$emit("filter-reset");
        },

        /**
         * change page with index {index}
         *
         * @param {Number} index page number
         *
         * @emits Event#filter-update-current-page
         */
        onChangePage(index) {
            this.$emit("filter-update-current-page", index);
        },

        onCategoryChange(categories) {
            this.queryFilter.filter.categories = categories?.map((cat) => cat.name);
        },

        onFolderChange(folder) {
            this.queryFilter.filter[this.positionFilterName] = folder?.id;
        },

        /**
         * Switch between descendants and children filter.
         * @param {Event} event Change event of the switch input.
         */
        onPositionFilterChange(event) {
            const value = event.target.checked;
            const newFilter = value ? 'ancestor' : 'parent';
            const oldFilter = value ? 'parent' : 'ancestor';

            this.queryFilter.filter[newFilter] = this.queryFilter.filter[oldFilter];
            delete this.queryFilter.filter[oldFilter];
        }
    }
};
