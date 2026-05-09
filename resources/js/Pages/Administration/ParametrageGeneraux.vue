<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import AlertMessage from '@/Components/Common/AlertMessage.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    config: Object,
    title: String,
});

const form = ref({ ...props.config });
const logoPreview = ref(props.config?.logo_path ? '/' + props.config.logo_path : null);
const faviconPreview = ref(props.config?.favicon_path ? '/' + props.config.favicon_path : null);
const logoLoginPreview = ref(props.config?.logo_login_path ? '/' + props.config.logo_login_path : null);
const logoFile = ref(null);
const faviconFile = ref(null);
const logoLoginFile = ref(null);
const saving = ref(false);
const activeTab = ref('identite');

const handleFile = (e, type) => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (type === 'logo') { logoFile.value = file; logoPreview.value = ev.target.result; }
        else if (type === 'favicon') { faviconFile.value = file; faviconPreview.value = ev.target.result; }
        else if (type === 'logo_login') { logoLoginFile.value = file; logoLoginPreview.value = ev.target.result; }
    };
    reader.readAsDataURL(file);
};

const submitForm = () => {
    saving.value = true;
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
        if (key !== 'id' && key !== 'created_at' && key !== 'updated_at' && key !== 'logo_path' && key !== 'favicon_path' && key !== 'logo_login_path') {
            formData.append(key, form.value[key] || '');
        }
    });
    if (logoFile.value) formData.append('logo', logoFile.value);
    if (faviconFile.value) formData.append('favicon', faviconFile.value);
    if (logoLoginFile.value) formData.append('logo_login', logoLoginFile.value);

    router.post(route('parametrage-generaux.update'), formData, {
        forceFormData: true,
        onSuccess: () => { saving.value = false; },
        onError: () => { saving.value = false; },
        onFinish: () => { saving.value = false; },
    });
};
</script>

<template>
    <Head :title="title" />
    <div class="body-wrapper">
        <div class="pg-page">
            <div class="pg-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1"><i class="fa fa-cogs me-2"></i> Paramétrage Généraux</h4>
                        <small class="opacity-75">Configuration globale de l'établissement</small>
                    </div>
                    <button class="btn btn-light" @click="submitForm" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="fa fa-save me-1"></i>
                        {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </div>

            <AlertMessage />

            <!-- Tabs -->
            <div class="pg-tabs">
                <button :class="{ active: activeTab === 'identite' }" @click="activeTab = 'identite'"><i class="fa fa-building me-1"></i> Identité</button>
                <button :class="{ active: activeTab === 'images' }" @click="activeTab = 'images'"><i class="fa fa-image me-1"></i> Images</button>
                <button :class="{ active: activeTab === 'coordonnees' }" @click="activeTab = 'coordonnees'"><i class="fa fa-phone me-1"></i> Coordonnées</button>
                <button :class="{ active: activeTab === 'reseaux' }" @click="activeTab = 'reseaux'"><i class="fa fa-share-alt me-1"></i> Réseaux</button>
                <button :class="{ active: activeTab === 'legal' }" @click="activeTab = 'legal'"><i class="fa fa-gavel me-1"></i> Légal</button>
                <button :class="{ active: activeTab === 'theme' }" @click="activeTab = 'theme'"><i class="fa fa-palette me-1"></i> Thème</button>
            </div>

            <!-- Tab: Identité -->
            <div v-show="activeTab === 'identite'" class="pg-card">
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label">Nom de l'établissement <span class="text-danger">*</span></label><input v-model="form.nom_etablissement" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Sigle</label><input v-model="form.sigle" type="text" class="form-control form-control-sm" placeholder="Ex: AS" /></div>
                    <div class="col-sm-6"><label class="form-label">Slogan</label><input v-model="form.slogan" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Devise monétaire</label><input v-model="form.devise" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea v-model="form.description" class="form-control form-control-sm" rows="3"></textarea></div>
                </div>
            </div>

            <!-- Tab: Images -->
            <div v-show="activeTab === 'images'" class="pg-card">
                <div class="row g-4">
                    <div class="col-sm-4">
                        <label class="form-label fw-bold">Logo principal</label>
                        <div class="img-upload-box">
                            <img v-if="logoPreview" :src="logoPreview" class="img-preview" />
                            <div v-else class="img-placeholder"><i class="fa fa-image fa-2x"></i><span>Aucun logo</span></div>
                            <label class="img-upload-btn"><i class="fa fa-upload me-1"></i> Changer<input type="file" accept="image/*" @change="handleFile($event, 'logo')" hidden /></label>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-bold">Favicon</label>
                        <div class="img-upload-box">
                            <img v-if="faviconPreview" :src="faviconPreview" class="img-preview img-favicon" />
                            <div v-else class="img-placeholder"><i class="fa fa-star fa-2x"></i><span>Aucun favicon</span></div>
                            <label class="img-upload-btn"><i class="fa fa-upload me-1"></i> Changer<input type="file" accept="image/*" @change="handleFile($event, 'favicon')" hidden /></label>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label fw-bold">Logo page de connexion</label>
                        <div class="img-upload-box">
                            <img v-if="logoLoginPreview" :src="logoLoginPreview" class="img-preview" />
                            <div v-else class="img-placeholder"><i class="fa fa-sign-in-alt fa-2x"></i><span>Aucun logo</span></div>
                            <label class="img-upload-btn"><i class="fa fa-upload me-1"></i> Changer<input type="file" accept="image/*" @change="handleFile($event, 'logo_login')" hidden /></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Coordonnées -->
            <div v-show="activeTab === 'coordonnees'" class="pg-card">
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Téléphone</label><input v-model="form.telephone" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Téléphone 2</label><input v-model="form.telephone2" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">WhatsApp</label><input v-model="form.whatsapp" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Site web</label><input v-model="form.site_web" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Adresse</label><input v-model="form.adresse" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-4"><label class="form-label">Ville</label><input v-model="form.ville" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-4"><label class="form-label">Pays</label><input v-model="form.pays" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-4"><label class="form-label">Boîte postale</label><input v-model="form.boite_postale" type="text" class="form-control form-control-sm" /></div>
                </div>
            </div>

            <!-- Tab: Réseaux sociaux -->
            <div v-show="activeTab === 'reseaux'" class="pg-card">
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label"><i class="fab fa-facebook me-1 text-primary"></i> Facebook</label><input v-model="form.facebook" type="text" class="form-control form-control-sm" placeholder="https://facebook.com/..." /></div>
                    <div class="col-sm-6"><label class="form-label"><i class="fab fa-twitter me-1" style="color:#1DA1F2;"></i> Twitter</label><input v-model="form.twitter" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label"><i class="fab fa-linkedin me-1" style="color:#0077B5;"></i> LinkedIn</label><input v-model="form.linkedin" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label"><i class="fab fa-instagram me-1" style="color:#E4405F;"></i> Instagram</label><input v-model="form.instagram" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label"><i class="fab fa-youtube me-1" style="color:#FF0000;"></i> YouTube</label><input v-model="form.youtube" type="text" class="form-control form-control-sm" /></div>
                </div>
            </div>

            <!-- Tab: Légal -->
            <div v-show="activeTab === 'legal'" class="pg-card">
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label">Numéro d'autorisation</label><input v-model="form.numero_autorisation" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Registre de commerce</label><input v-model="form.registre_commerce" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-6"><label class="form-label">Numéro fiscal</label><input v-model="form.numero_fiscal" type="text" class="form-control form-control-sm" /></div>
                </div>
            </div>

            <!-- Tab: Thème -->
            <div v-show="activeTab === 'theme'" class="pg-card">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label">Couleur primaire</label>
                        <div class="d-flex align-items-center gap-2">
                            <input v-model="form.couleur_primaire" type="color" class="form-control form-control-color" style="width:50px;height:38px;" />
                            <input v-model="form.couleur_primaire" type="text" class="form-control form-control-sm" />
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Couleur secondaire</label>
                        <div class="d-flex align-items-center gap-2">
                            <input v-model="form.couleur_secondaire" type="color" class="form-control form-control-color" style="width:50px;height:38px;" />
                            <input v-model="form.couleur_secondaire" type="text" class="form-control form-control-sm" />
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Couleur accent</label>
                        <div class="d-flex align-items-center gap-2">
                            <input v-model="form.couleur_accent" type="color" class="form-control form-control-color" style="width:50px;height:38px;" />
                            <input v-model="form.couleur_accent" type="text" class="form-control form-control-sm" />
                        </div>
                    </div>
                    <div class="col-sm-4"><label class="form-label">Langue par défaut</label><input v-model="form.langue_par_defaut" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-4"><label class="form-label">Fuseau horaire</label><input v-model="form.fuseau_horaire" type="text" class="form-control form-control-sm" /></div>
                    <div class="col-sm-4"><label class="form-label">Année scolaire courante</label><input v-model="form.annee_scolaire_courante" type="number" class="form-control form-control-sm" /></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pg-header { background: linear-gradient(135deg, #0c1e36 0%, #0B5697 50%, #0FBCAF 100%); border-radius: 12px; padding: 1.2rem 1.5rem; color: white; margin-bottom: 15px; }
.pg-tabs { display: flex; gap: 4px; margin-bottom: 15px; flex-wrap: wrap; }
.pg-tabs button { padding: 8px 16px; border: none; background: white; border-radius: 8px 8px 0 0; font-weight: 600; color: #777; cursor: pointer; font-size: 12px; transition: all 0.2s; }
.pg-tabs button.active { color: #E5590C; border-bottom: 3px solid #E5590C; background: white; }
.pg-tabs button:hover:not(.active) { color: #333; }
.pg-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; }

/* Image upload */
.img-upload-box { border: 2px dashed #e0e0e0; border-radius: 12px; padding: 15px; text-align: center; background: #fafafa; transition: all 0.2s; }
.img-upload-box:hover { border-color: #E5590C; background: #fff8f5; }
.img-preview { max-width: 100%; max-height: 120px; border-radius: 8px; margin-bottom: 10px; object-fit: contain; }
.img-favicon { max-height: 64px; }
.img-placeholder { padding: 20px 0; color: #ccc; }
.img-placeholder i { display: block; margin-bottom: 8px; }
.img-placeholder span { font-size: 12px; }
.img-upload-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 14px; border-radius: 6px; background: #E5590C; color: white; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.img-upload-btn:hover { background: #ff6b1a; }
</style>
