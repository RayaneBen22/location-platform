<?php

require_once __DIR__ . '/../app/agent.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = positiveIdentifier($_POST['id'] ?? null);
    $active = submittedString($_POST, 'is_active');

    if (!$id || !in_array($active, ['0', '1'], true)) {
        $error = 'Compte ou action invalide.';
    } else {
        $query = database()->prepare("UPDATE users SET is_active = :active WHERE id = :id AND role = 'client'");
        $query->execute(['id' => $id, 'active' => (int) $active]);

        if ($query->rowCount() === 0) {
            $error = 'Ce compte est introuvable, déjà dans cet état ou réservé à l’équipe.';
        } else {
            flashMessage('Le statut du compte client a été mis à jour.');
            redirectTo('agent/utilisateurs.php');
        }
    }
}

$search = mb_substr(submittedString($_GET, 'q'), 0, 100);
$query = database()->prepare("SELECT COUNT(*) FROM users WHERE role = 'client' AND (name LIKE :name OR email LIKE :email)");
$query->execute(['name' => '%' . $search . '%', 'email' => '%' . $search . '%']);
$total = (int) $query->fetchColumn();
$pages = max(1, (int) ceil($total / 25));
$page = min(positiveIdentifier($_GET['page'] ?? 1) ?? 1, $pages);
$query = database()->prepare("SELECT id, name, email, phone, is_active, created_at FROM users WHERE role = 'client' AND (name LIKE :name OR email LIKE :email) ORDER BY id DESC LIMIT :limit OFFSET :offset");
$query->bindValue(':name', '%' . $search . '%');
$query->bindValue(':email', '%' . $search . '%');
$query->bindValue(':limit', 25, PDO::PARAM_INT);
$query->bindValue(':offset', ($page - 1) * 25, PDO::PARAM_INT);
$query->execute();
$clients = $query->fetchAll();
pageHeader('Gestion des clients');
?>
<section class="container page-intro"><p class="eyebrow">ESPACE ÉQUIPE</p><h1>Vos clients.</h1><p class="lead">Gérez les accès en préservant les comptes et leur historique.</p></section>
<div class="container section-compact"><?php agentNavigation('utilisateurs.php'); ?><?php if ($error): ?><p class="notice notice-error" role="alert"><?= escape($error) ?></p><?php endif; ?><form class="inline-actions" action="<?= escape(appUrl('agent/utilisateurs.php')) ?>" method="get"><div class="field"><label for="client-search">Nom ou email</label><input id="client-search" name="q" type="search" maxlength="100" value="<?= escape($search) ?>"></div><button class="button button-small" type="submit">Rechercher</button></form><p class="field-help">La suspension empêche la connexion et les nouvelles réservations. Les réservations existantes restent à traiter. Les agents ne peuvent pas modifier les rôles ni suspendre d’autres agents.</p><div class="table-wrap"><table><thead><tr><th>Client</th><th>Coordonnées</th><th>Accès</th><th>Action</th></tr></thead><tbody><?php foreach ($clients as $client): ?><tr><td><?= escape($client['name']) ?></td><td><?= escape($client['email']) ?><br><?= escape($client['phone']) ?></td><td><?= $client['is_active'] ? 'Actif' : 'Suspendu' ?></td><td><form action="<?= escape(appUrl('agent/utilisateurs.php')) ?>" method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $client['id'] ?>"><input type="hidden" name="is_active" value="<?= $client['is_active'] ? '0' : '1' ?>"><button class="button button-small button-outline" type="submit"><?= $client['is_active'] ? 'Suspendre' : 'Réactiver' ?></button></form></td></tr><?php endforeach; ?><?php if (!$clients): ?><tr><td colspan="4">Aucun client trouvé.</td></tr><?php endif; ?></tbody></table></div><?php if ($pages > 1): ?><nav class="pagination" aria-label="Pages des clients"><?php for ($number = max(1, $page - 2); $number <= min($pages, $page + 2); $number++): ?><a href="<?= escape(appUrl('agent/utilisateurs.php?' . http_build_query(['q' => $search, 'page' => $number]))) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?></nav><?php endif; ?></div>
<?php pageFooter(); ?>
