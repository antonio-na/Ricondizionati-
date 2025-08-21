const { createApp } = Vue;

const API_BASE_URL = 'http://localhost/backend/api'; // Assumendo che il backend sia servito localmente. Da cambiare in produzione.

createApp({
    data() {
        return {
            models: [],
            capacities: [],
            conditions: [],
            selection: {
                model: null,
                capacity: null,
                condition: null,
            },
            ui: {
                model_selected: null,
                capacity_selected: null,
                condition_selected: null,
                show_user_form: false,
                request_submitted: false,
            },
            valuation_price: null,
            loading_price: false,
            submitting: false,
            error: null,
            user: {
                name: '',
                email: '',
                address: '',
                payment_method: '',
            }
        };
    },
    methods: {
        async fetchInitialData() {
            try {
                const response = await fetch(`${API_BASE_URL}/get_devices.php`);
                if (!response.ok) throw new Error('Errore di rete.');
                const result = await response.json();
                if (result.success) {
                    this.models = result.data.models;
                    this.capacities = result.data.capacities;
                    this.conditions = result.data.conditions;
                } else {
                    throw new Error(result.message || 'Errore nel caricamento dei dati.');
                }
            } catch (err) {
                this.error = err.message;
            }
        },
        selectModel(model) {
            this.selection.model = model;
            this.ui.model_selected = model;
        },
        selectCapacity(capacity) {
            this.selection.capacity = capacity;
            this.ui.capacity_selected = capacity;
        },
        selectCondition(condition) {
            this.selection.condition = condition;
            this.ui.condition_selected = condition;
            this.getValuationPrice();
        },
        async getValuationPrice() {
            if (!this.selection.model || !this.selection.capacity || !this.selection.condition) return;
            this.loading_price = true;
            this.valuation_price = null;
            this.error = null;
            try {
                const params = new URLSearchParams({
                    model_id: this.selection.model.id,
                    capacity_id: this.selection.capacity.id,
                    condition_id: this.selection.condition.id,
                });
                const response = await fetch(`${API_BASE_URL}/get_price.php?${params}`);
                if (!response.ok) throw new Error('Errore di rete nel recupero del prezzo.');
                const result = await response.json();
                if (result.success) {
                    this.valuation_price = result.price;
                } else {
                    this.valuation_price = null;
                }
            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading_price = false;
            }
        },
        resetSelection() {
            this.selection = { model: null, capacity: null, condition: null };
            this.ui = { model_selected: null, capacity_selected: null, condition_selected: null, show_user_form: false, request_submitted: false };
            this.valuation_price = null;
            this.error = null;
            this.user = { name: '', email: '', address: '', payment_method: '' };
        },
        async submitRequest() {
            this.submitting = true;
            this.error = null;
            try {
                const payload = {
                    user_name: this.user.name,
                    user_email: this.user.email,
                    user_address: this.user.address,
                    payment_method: this.user.payment_method,
                    device: {
                        model_id: this.selection.model.id,
                        capacity_id: this.selection.capacity.id,
                        condition_id: this.selection.condition.id,
                    }
                };
                const response = await fetch(`${API_BASE_URL}/submit_request.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Si è verificato un errore durante l\'invio.');
                }
                this.ui.request_submitted = true;
            } catch (err) {
                this.error = err.message;
            } finally {
                this.submitting = false;
            }
        }
    },
    created() {
        this.fetchInitialData();
    }
}).mount('#app');
