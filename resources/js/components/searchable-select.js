export default function searchableSelect(options, initialSelected = null, placeholder = 'Select…') {
    return {
        open: false,
        search: '',
        selected: initialSelected,
        options: options,

        get filtered() {
            if (!this.search) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },

        get selectedLabel() {
            const opt = this.options.find(o => o.id === this.selected);
            return opt ? opt.label : placeholder;
        },

        select(id) {
            this.selected = id;
            this.open = false;
            this.search = '';
        },
    };
}