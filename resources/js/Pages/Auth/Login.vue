<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    ui_home_page: 'companies',
});

const normalizeHomePage = (homePage) => ['companies', 'dashboard', 'method'].includes(homePage)
    ? homePage
    : 'companies';

const preferredHomePage = () => {
    try {
        const preferences = JSON.parse(localStorage.getItem('sicurezzachiara.theme.current') || '{}');

        return normalizeHomePage(preferences.homePage);
    } catch (error) {
        localStorage.removeItem('sicurezzachiara.theme.current');

        return 'companies';
    }
};

const submit = () => {
    form.transform(data => ({
        ...data,
        ui_home_page: preferredHomePage(),
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<script>
export default {
    data() {
        return {
            togglePassword: false
        }
    }
}
</script>

<template>
    <Head title="Log in" />

    <div class="auth-page-wrapper sc-login-page py-5 d-flex justify-content-center align-items-center min-vh-100">
        <div class="auth-page-content sc-login-content overflow-hidden">
            <BContainer>
                <BRow>
                    <BCol lg="12">
                        <BCard no-body class="sc-login-card overflow-hidden mb-0">
                            <BRow class="g-0">
                                <BCol lg="6">
                                    <div class="sc-login-story p-lg-5 p-4 h-100">
                                        <div class="sc-login-story-inner position-relative h-100 d-flex flex-column justify-content-center">
                                            <div class="sc-login-brand-block">
                                                <Link href="/" class="sc-login-brand d-inline-block">
                                                    <img src="@assets/images/logo-payoff-light.png" alt="SicurezzaChiara - Ordine nella sicurezza" height="90">
                                                </Link>
                                            </div>

                                            <div class="sc-login-story-copy">
                                                <h2 class="text-white mb-3">Governo operativo della sicurezza aziendale</h2>
                                                <p class="text-white-50 fs-15 mb-3">
                                                    Rischi, misure, scadenze e DVR light in un unico workspace.
                                                </p>
                                                <p class="sc-login-story-note mb-0">
                                                    Il sistema propone, il consulente verifica e governa.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </BCol>

                                <BCol lg="6">
                                    <BCardBody class="sc-login-form-panel p-lg-5 p-4 h-100 d-flex flex-column justify-content-center">
                                        <div class="sc-login-form-inner">
                                            <div>
                                                <h5 class="text-primary">Accedi a SicurezzaChiara</h5>
                                                <p class="text-muted">Inserisci le credenziali per accedere al tuo workspace operativo.</p>
                                            </div>

                                            <div v-if="status" class="alert alert-success text-success mt-4">
                                                {{ status }}
                                            </div>

                                            <div class="mt-4">
                                                <form @submit.prevent="submit">
                                                    <div class="mb-3">
                                                        <InputLabel for="email" value="Email" />
                                                        <TextInput id="email" v-model="form.email" type="email" class="form-control" autofocus placeholder="Inserisci email" autocomplete="email" required :class="{ 'is-invalid': form.errors.email }" />
                                                        <InputError :message="form.errors.email" />
                                                    </div>

                                                    <div class="mb-3">
                                                        <div class="float-end">
                                                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-muted">Password dimenticata?</Link>
                                                        </div>
                                                        <InputLabel for="password" value="Password" />
                                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                                            <input :type="togglePassword ? 'text' : 'password'" class="form-control pe-5" placeholder="Inserisci password" id="password-input" v-model="form.password" autocomplete="password" required :class="{ 'is-invalid': form.errors.password }">
                                                            <BButton variant="link" class="position-absolute end-0 top-0 text-decoration-none text-muted" type="button" id="password-addon" @click="togglePassword = !togglePassword">
                                                                <i class="ri-eye-fill align-middle"></i>
                                                            </BButton>
                                                            <InputError :message="form.errors.password" />
                                                        </div>
                                                    </div>

                                                    <div class="form-check">
                                                        <Checkbox v-model:checked="form.remember" name="remember" class="form-check-input" id="auth-remember-check" />
                                                        <label class="form-check-label" for="auth-remember-check">Ricordami</label>
                                                    </div>

                                                    <div class="mt-4">
                                                        <BButton variant="success" class="w-100" type="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Accedi</BButton>
                                                    </div>

                                                    <p class="sc-login-access-note text-muted text-center mb-0 mt-3">
                                                        Accesso riservato ai consulenti e agli utenti autorizzati.
                                                    </p>
                                                </form>
                                            </div>
                                        </div>
                                    </BCardBody>
                                </BCol>
                            </BRow>
                        </BCard>
                    </BCol>
                </BRow>
            </BContainer>
        </div>

        <footer class="footer">
            <BContainer>
                <BRow>
                    <BCol lg="12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy; {{ new Date().getFullYear() }} SicurezzaChiara &middot; Piattaforma per il governo operativo della sicurezza aziendale</p>
                        </div>
                    </BCol>
                </BRow>
            </BContainer>
        </footer>
    </div>
</template>

<style scoped>
.sc-login-page {
    background:
        radial-gradient(circle at 12% 20%, rgba(15, 167, 111, 0.22), transparent 30%),
        radial-gradient(circle at 84% 12%, rgba(66, 103, 178, 0.18), transparent 32%),
        linear-gradient(135deg, #eef7f3 0%, #f8fbff 48%, #e9f2ef 100%);
}

.sc-login-content {
    width: 100%;
}

.sc-login-card {
    border: 0;
    box-shadow: 0 18px 45px rgba(22, 35, 48, 0.14);
}

.sc-login-story {
    position: relative;
    overflow: hidden;
    min-height: 520px;
    background:
        linear-gradient(140deg, rgba(9, 61, 55, 0.96), rgba(21, 109, 86, 0.94)),
        radial-gradient(circle at 88% 20%, rgba(122, 210, 164, 0.24), transparent 34%);
    color: #fff;
}

.sc-login-story::before,
.sc-login-story::after {
    position: absolute;
    content: "";
    border-radius: 999px;
    pointer-events: none;
}

.sc-login-story::before {
    right: -72px;
    top: -72px;
    width: 190px;
    height: 190px;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.sc-login-story::after {
    left: -80px;
    bottom: -96px;
    width: 220px;
    height: 220px;
    background: rgba(255, 255, 255, 0.06);
}

.sc-login-story-inner {
    z-index: 1;
}

.sc-login-brand img {
    filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.24));
}

.sc-login-brand-block {
    margin-bottom: 3.5rem;
}

.sc-login-story-copy h2 {
    max-width: 390px;
    font-size: 1.55rem;
    font-weight: 600;
    line-height: 1.22;
    color: #c7c9c8 !important;
}

.sc-login-story-copy p {
    max-width: 500px;
    color: rgba(255, 255, 255, 0.72) !important;
}

.sc-login-story-note {
    max-width: 420px;
    color: rgba(255, 255, 255, 0.82);
}

.sc-login-access-note {
    font-size: 0.8125rem;
}

@media (max-width: 991.98px) {
    .sc-login-story {
        min-height: auto;
    }

    .sc-login-story-copy h2 {
        font-size: 1.55rem;
    }
}

@media (max-width: 575.98px) {
    .sc-login-page {
        padding-top: 1.5rem !important;
        padding-bottom: 1.5rem !important;
    }

    .sc-login-story-copy h2 {
        font-size: 1.35rem;
    }

    .sc-login-points {
        gap: 0.75rem !important;
    }
}
</style>
