<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineOptions({
    layout: DashboardLayout
});

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// Formatage des montants
function formatAmount(amount) {
    if (amount === null || amount === undefined) return '0';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Obtenir la classe CSS selon le statut
function getStatusClass(statut) {
    const classes = {
        'effectuee': 'badge-success',
        'en_attente': 'badge-warning',
        'echouee': 'badge-danger',
        'annulee': 'badge-secondary',
    };
    return classes[statut] || 'badge-info';
}

// Obtenir le libellé du statut
function getStatusLabel(statut) {
    const labels = {
        'effectuee': 'Effectuée',
        'en_attente': 'En attente',
        'echouee': 'Échouée',
        'annulee': 'Annulée',
    };
    return labels[statut] || statut;
}
</script>

<template>
    <Head title="Transactions" />
    
    <div class="body-wrapper">
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">Transactions</h3>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-control">
                                <option value="">Tous</option>
                                <option value="recharge">Recharge</option>
                                <option value="transfert">Transfert</option>
                                <option value="retrait">Retrait</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select class="form-control">
                                <option value="">Tous</option>
                                <option value="effectuee">Effectuée</option>
                                <option value="en_attente">En attente</option>
                                <option value="echouee">Échouée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date début</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des transactions -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!transactions.data || transactions.data.length === 0">
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>Aucune transaction trouvée</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="(transaction, index) in transactions.data" :key="transaction.id">
                                    <td>{{ index + 1 }}</td>
                                    <td class="text-capitalize">{{ transaction.type }}</td>
                                    <td>
                                        <span :class="transaction.montant >= 0 ? 'text-success' : 'text-danger'">
                                            {{ formatAmount(Math.abs(transaction.montant)) }} {{ transaction.devise }}
                                        </span>
                                    </td>
                                    <td>
                                        <span :class="['badge', getStatusClass(transaction.statut)]">
                                            {{ getStatusLabel(transaction.statut) }}
                                        </span>
                                    </td>
                                    <td>{{ transaction.created_at }}</td>
                                    <td>{{ transaction.reference || '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.links" class="d-flex justify-content-center mt-4">
                        <nav>
                            <ul class="pagination">
                                <li 
                                    v-for="link in transactions.links" 
                                    :key="link.label"
                                    :class="['page-item', { active: link.active, disabled: !link.url }]"
                                >
                                    <Link 
                                        v-if="link.url"
                                        :href="link.url" 
                                        class="page-link"
                                        v-html="link.label"
                                    />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    border: none;
}

.card-body {
    padding: 20px;
}

.form-label {
    font-weight: 500;
    color: #2d3748;
    margin-bottom: 5px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 10px 15px;
}

.custom-table {
    width: 100%;
    border-collapse: collapse;
}

.custom-table thead th {
    background: #f8f9fc;
    padding: 15px;
    font-weight: 600;
    color: #2d3748;
    border-bottom: 2px solid #e2e8f0;
}

.custom-table tbody td {
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.custom-table tbody tr:hover {
    background: #f8f9fc;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.badge-success { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
.badge-warning { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
.badge-danger { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
.badge-secondary { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
.badge-info { background: rgba(52, 152, 219, 0.1); color: #3498db; }

.btn-info {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
}

.btn-info:hover {
    background: #3498db;
    color: #fff;
}

.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 5px;
}

.page-item .page-link {
    padding: 8px 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    color: #2d3748;
    text-decoration: none;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #3498DB 0%, #3498DB 100%);
    color: #fff;
    border-color: transparent;
}

.page-item.disabled .page-link {
    opacity: 0.5;
    cursor: not-allowed;
}

.text-success { color: #2ecc71; }
.text-danger { color: #e74c3c; }
.text-muted { color: #6c757d; }
.text-center { text-align: center; }
.text-capitalize { text-transform: capitalize; }
.py-4 { padding: 1.5rem 0; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-4 { margin-top: 1.5rem; }
.d-flex { display: flex; }
.justify-content-center { justify-content: center; }
</style>
