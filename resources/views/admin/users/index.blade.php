<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs — SICORE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main id="user-management" class="mx-auto max-w-7xl p-4 sm:p-8">
    <header class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Administration</p><h1 class="mt-1 text-3xl font-bold">Utilisateurs</h1><p class="mt-2 text-slate-500">Gérez les accès et le périmètre organisationnel.</p></div>
        <button data-action="new" class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white shadow-sm hover:bg-indigo-700">Ajouter un utilisateur</button>
    </header>
    <div data-alert class="mb-5 hidden rounded-lg border px-4 py-3 text-sm" role="alert"></div>
    <div class="mb-5 flex justify-end">
        <label class="grid w-full max-w-xs gap-2 text-sm font-medium">Type de structure
            <select data-structure-type-filter class="rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                <option value="">Tous les types</option>
            </select>
        </label>
    </div>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">Utilisateur</th><th class="px-5 py-4">Rôle</th><th class="px-5 py-4">Structure</th><th class="px-5 py-4">Statut</th><th class="px-5 py-4 text-right">Action</th></tr></thead>
            <tbody data-users class="divide-y divide-slate-100"></tbody>
        </table></div>
        <div data-empty class="hidden px-5 py-12 text-center text-slate-500">Aucun utilisateur trouvé.</div>
    </section>
    <dialog data-dialog class="w-full max-w-2xl rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
        <form data-form class="p-6 sm:p-8">
            <div class="mb-6 flex items-start justify-between"><div><p class="text-sm font-semibold text-indigo-600">Compte utilisateur</p><h2 data-title class="text-2xl font-bold">Nouvel utilisateur</h2></div><button type="button" data-action="close" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Fermer">✕</button></div>
            <input type="hidden" name="id">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium">Prénom<input name="prenom" required class="rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label class="grid gap-2 text-sm font-medium">Nom<input name="nom" required class="rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label class="grid gap-2 text-sm font-medium sm:col-span-2">E-mail<input name="email" type="email" required class="rounded-lg border border-slate-300 px-3 py-2.5"></label>
                <label data-password-field class="grid gap-2 text-sm font-medium sm:col-span-2">Mot de passe<input name="password" type="password" minlength="8" class="rounded-lg border border-slate-300 px-3 py-2.5"><span class="text-xs font-normal text-slate-500">8 caractères minimum.</span></label>
                <label class="grid gap-2 text-sm font-medium">Rôle<select name="role_id" required class="rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select></label>
                <label class="grid gap-2 text-sm font-medium">Statut<select name="statut" required class="rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option value="actif">Actif</option><option value="inactif">Inactif</option></select></label>
                <div class="sm:col-span-2 mt-2">
                    <h3 class="mb-4 text-2xl font-bold">Accès organisationnel</h3>
                    <p class="mb-5 text-sm text-slate-500">Choisissez une structure nationale ou une IA et son IEF.</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium">Périmètre<select name="perimetre" data-perimetre-select class="rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option value="national">National</option><option value="regional">Régional</option></select></label>
                        <label data-structure-field class="grid gap-2 text-sm font-medium">Structure nationale<select name="lieu_service_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select><span data-structure-hint class="text-xs font-normal text-slate-500">Obligatoire pour un compte métier.</span></label>
                    </div>
                </div>
            </div>
            <div data-errors class="mt-5 hidden rounded-lg bg-red-50 p-4 text-sm text-red-700"></div>
            <div class="mt-7 flex justify-end gap-3"><button type="button" data-action="close" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold">Annuler</button><button data-submit type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white disabled:cursor-wait disabled:opacity-60">Enregistrer</button></div>
        </form>
    </dialog>
</main>
</body>
</html>
