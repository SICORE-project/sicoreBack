import './bootstrap';

const root = document.querySelector('#user-management');

if (root) {
    const token = localStorage.getItem('access_token') || localStorage.getItem('token');
    const api = window.axios.create({
        baseURL: '/api/admin',
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
    const elements = {
        rows: root.querySelector('[data-users]'), empty: root.querySelector('[data-empty]'),
        alert: root.querySelector('[data-alert]'), dialog: root.querySelector('[data-dialog]'),
        form: root.querySelector('[data-form]'), title: root.querySelector('[data-title]'),
        errors: root.querySelector('[data-errors]'), structure: root.querySelector('[data-structure-field]'),
        structureHint: root.querySelector('[data-structure-hint]'),
        structureTypeFilter: root.querySelector('[data-structure-type-filter]'),
        password: root.querySelector('[data-password-field]'), submit: root.querySelector('[data-submit]'),
    };
    let users = [];
    let roles = [];
    let structures = [];

    const escape = (value = '') => String(value).replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    })[char]);
    const showAlert = (message, error = false) => {
        elements.alert.textContent = message;
        elements.alert.className = `mb-5 rounded-lg border px-4 py-3 text-sm ${error ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`;
    };
    const roleIsBusiness = () => {
        const role = roles.find(item => String(item.id) === elements.form.role_id.value);
        return role && role.niveau !== 'systeme';
    };
    const selectedRole = () => roles.find(item => String(item.id) === elements.form.role_id.value);
    const allowedStructures = role => role?.niveau === 'admin_metier'
        ? structures.filter(structure => structure.type.toUpperCase() === 'DAGE')
        : structures;
    const updateStructureRequirement = (preferredStructureId = null) => {
        const role = selectedRole();
        const required = roleIsBusiness();
        const previousValue = preferredStructureId ?? elements.form.structure_organisationnelle_id.value;
        const availableStructures = allowedStructures(role);

        elements.structure.classList.toggle('hidden', !required);
        elements.form.structure_organisationnelle_id.required = Boolean(required);
        elements.form.structure_organisationnelle_id.innerHTML = '<option value="">Sélectionner une structure</option>'
            + availableStructures.map(structure => `<option value="${structure.id}">${escape(structure.type)} — ${escape(structure.libelle)}</option>`).join('');
        elements.form.structure_organisationnelle_id.value = required
            && availableStructures.some(structure => String(structure.id) === String(previousValue))
            ? String(previousValue)
            : '';
        elements.structureHint.textContent = role?.niveau === 'admin_metier'
            ? 'Un administrateur métier doit obligatoirement être rattaché à la DAGE.'
            : 'Obligatoire pour un compte métier.';
    };
    const render = () => {
        elements.empty.classList.toggle('hidden', users.length > 0);
        elements.rows.innerHTML = users.map(user => {
            const structure = user.structure_organisationnelle;
            return `<tr class="text-sm">
                <td class="px-5 py-4"><div class="font-semibold">${escape(user.prenom)} ${escape(user.nom)}</div><div class="text-slate-500">${escape(user.email)}</div></td>
                <td class="px-5 py-4">${escape(user.role?.nom || '—')}</td>
                <td class="px-5 py-4">${structure ? `<span class="font-medium">${escape(structure.libelle)}</span><div class="text-xs uppercase text-slate-500">${escape(structure.type)}</div>` : '<span class="text-slate-400">Non rattaché</span>'}</td>
                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold ${user.statut === 'actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'}">${escape(user.statut)}</span></td>
                <td class="px-5 py-4 text-right"><button data-edit="${user.id}" class="font-semibold text-indigo-600 hover:text-indigo-800">Modifier</button></td>
            </tr>`;
        }).join('');
    };
    const populateStructureTypeFilter = () => {
        const selected = elements.structureTypeFilter.value;
        const types = [...new Set(structures.map(structure => structure.type).filter(Boolean))]
            .sort((left, right) => left.localeCompare(right, 'fr'));
        elements.structureTypeFilter.innerHTML = '<option value="">Tous les types</option>'
            + types.map(type => `<option value="${escape(type)}">${escape(type)}</option>`).join('');
        elements.structureTypeFilter.value = selected;
    };
    const showErrors = (response) => {
        const validation = response?.data?.errors;
        const messages = validation ? Object.values(validation).flat() : [response?.data?.message || 'Une erreur est survenue.'];
        elements.errors.innerHTML = messages.map(escape).join('<br>');
        elements.errors.classList.remove('hidden');
    };
    const openForm = (user = null) => {
        elements.form.reset();
        elements.errors.classList.add('hidden');
        elements.form.id.value = user?.id || '';
        elements.form.nom.value = user?.nom || '';
        elements.form.prenom.value = user?.prenom || '';
        elements.form.email.value = user?.email || '';
        elements.form.role_id.value = user?.role?.id || '';
        elements.form.statut.value = user?.statut || 'actif';
        elements.form.structure_organisationnelle_id.value = user?.structure_organisationnelle?.id || '';
        elements.title.textContent = user ? 'Modifier l’utilisateur' : 'Nouvel utilisateur';
        elements.password.classList.toggle('hidden', Boolean(user));
        elements.form.password.required = !user;
        updateStructureRequirement(user?.structure_organisationnelle?.id);
        elements.dialog.showModal();
    };
    const load = async (reloadReferences = true) => {
        if (!token) {
            showAlert('Aucun jeton de connexion trouvé. Connectez-vous avant d’ouvrir cette page.', true);
            return;
        }
        try {
            const params = elements.structureTypeFilter.value
                ? { type_structure: elements.structureTypeFilter.value }
                : {};
            const [userResponse, roleResponse, structureResponse] = await Promise.all([
                api.get('/users', { params }),
                reloadReferences ? api.get('/roles/all') : Promise.resolve({ data: { data: roles } }),
                reloadReferences ? api.get('/structures-organisationnelles') : Promise.resolve({ data: { data: structures } }),
            ]);
            users = userResponse.data.data;
            roles = roleResponse.data.data;
            structures = structureResponse.data.data;
            populateStructureTypeFilter();
            elements.form.role_id.innerHTML = '<option value="">Sélectionner un rôle</option>' + roles.map(role => `<option value="${role.id}">${escape(role.nom)}</option>`).join('');
            updateStructureRequirement();
            render();
        } catch (error) {
            showAlert(error.response?.status === 401 ? 'Votre session a expiré. Veuillez vous reconnecter.' : 'Impossible de charger les utilisateurs.', true);
        }
    };

    root.querySelector('[data-action="new"]').addEventListener('click', () => openForm());
    root.querySelectorAll('[data-action="close"]').forEach(button => button.addEventListener('click', () => elements.dialog.close()));
    elements.form.role_id.addEventListener('change', updateStructureRequirement);
    elements.structureTypeFilter.addEventListener('change', () => load(false));
    elements.rows.addEventListener('click', event => {
        const button = event.target.closest('[data-edit]');
        if (button) openForm(users.find(user => String(user.id) === button.dataset.edit));
    });
    elements.form.addEventListener('submit', async event => {
        event.preventDefault();
        elements.errors.classList.add('hidden');
        const data = Object.fromEntries(new FormData(elements.form));
        const id = data.id;
        delete data.id;
        if (!data.password) delete data.password;
        if (!roleIsBusiness()) data.structure_organisationnelle_id = null;
        elements.submit.disabled = true;
        elements.submit.textContent = 'Enregistrement…';
        try {
            if (id) await api.put(`/users/${id}`, data); else await api.post('/users', data);
            elements.dialog.close();
            showAlert(id ? 'Utilisateur mis à jour.' : 'Utilisateur créé.');
            await load();
        } catch (error) {
            showErrors(error.response);
        } finally {
            elements.submit.disabled = false;
            elements.submit.textContent = 'Enregistrer';
        }
    });
    load();
}
