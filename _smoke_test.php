<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

echo "\n=== SMOKE TESTS — Endpoints critiques (post fixes 2026-07-07) ===\n\n";

$results = [];

// ---------- 1. Jour férié : create avec est_recurrent ----------
try {
    DB::table('jours_feries')->where('code', 'SMOKE_JF')->delete();
    $id = DB::table('jours_feries')->insertGetId([
        'code'          => 'SMOKE_JF',
        'libelle'       => 'Test férié',
        'date'          => '2026-01-01',
        'jour'          => 1,
        'mois'          => 1,
        'annee'         => 2026,
        'pays_id'       => DB::table('pays')->value('id'),
        'est_recurrent' => true,
        'etat'          => 'actif',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
    $ok = DB::table('jours_feries')->where('id', $id)->where('est_recurrent', 1)->exists();
    DB::table('jours_feries')->where('id', $id)->delete();
    $results[] = ['JourFerie est_recurrent', $ok ? 'OK' : 'FAIL', ''];
} catch (\Throwable $e) {
    $results[] = ['JourFerie est_recurrent', 'FAIL', $e->getMessage()];
}

// ---------- 2. Catégorie fourniture ----------
try {
    DB::table('categories_fournitures')->where('libelle', 'SMOKE_CATF')->delete();
    $id = DB::table('categories_fournitures')->insertGetId([
        'libelle'    => 'SMOKE_CATF',
        'code'       => 'SMK_CF',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $ok = $id > 0;
    DB::table('categories_fournitures')->where('id', $id)->delete();
    $results[] = ['CategorieFourniture create', $ok ? 'OK' : 'FAIL', ''];
} catch (\Throwable $e) {
    $results[] = ['CategorieFourniture create', 'FAIL', $e->getMessage()];
}

// ---------- 3. Catégorie apprenant ----------
try {
    $count = Modules\Parametrage\Entities\CategorieApprenant::actif()->count();
    $ok = $count >= 3;
    $results[] = ['CategorieApprenant actif count', $ok ? 'OK' : 'FAIL', "count=$count"];
} catch (\Throwable $e) {
    $results[] = ['CategorieApprenant entity', 'FAIL', $e->getMessage()];
}

// ---------- 4. Apprenant + commentaire + categorie_apprenant_id ----------
try {
    $catId = DB::table('categorie_apprenants')->value('id');
    $classId = DB::table('classes')->value('id');
    $ecoleId = DB::table('ecoles')->value('id');
    $matricule = 'SMOKE_APP_' . rand(100, 999);
    $id = DB::table('apprenants')->insertGetId([
        'matricule'              => $matricule,
        'nom'                    => 'Smoke',
        'prenoms'                => 'Test',
        'classe_id'              => $classId,
        'ecole_id'               => $ecoleId,
        'categorie_apprenant_id' => $catId,
        'commentaire'            => 'Test commentaire libre',
        'statut'                 => 'ACTIF',
        'created_at'             => now(),
        'updated_at'             => now(),
    ]);
    $row = DB::table('apprenants')->where('id', $id)->first();
    $ok = $row && $row->commentaire === 'Test commentaire libre' && $row->categorie_apprenant_id == $catId;
    DB::table('apprenants')->where('id', $id)->delete();
    $results[] = ['Apprenant + commentaire + categorie', $ok ? 'OK' : 'FAIL', ''];
} catch (\Throwable $e) {
    $results[] = ['Apprenant commentaire/categorie', 'FAIL', $e->getMessage()];
}

// ---------- 5. Enseignant : titre_civilite_id + telephone2 ----------
try {
    $titreId = DB::table('titres_civilites')->value('id');
    $userId = DB::table('users')->value('id');
    $id = DB::table('enseignants')->insertGetId([
        'user_id'           => $userId,
        'nom'               => 'Smoke',
        'prenoms'           => 'Enseignant',
        'titre_civilite_id' => $titreId,
        'telephone'         => '0700000101',
        'telephone2'        => '0700000102',
        'statut'            => 'actif',
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
    $row = DB::table('enseignants')->where('id', $id)->first();
    $ok = $row && $row->telephone2 === '0700000102' && $row->titre_civilite_id == $titreId;
    DB::table('enseignants')->where('id', $id)->delete();
    $results[] = ['Enseignant + titre + tel2', $ok ? 'OK' : 'FAIL', ''];
} catch (\Throwable $e) {
    $results[] = ['Enseignant titre/tel2', 'FAIL', $e->getMessage()];
}

// ---------- 6. Users email non-unique ----------
try {
    $email = 'shared.smoke@example.com';
    DB::table('users')->where('email', $email)->delete();
    $u1 = App\Services\AutoUserCreator::forProfile([
        'nom'       => 'U1', 'prenoms' => 'x',
        'email'     => $email, 'telephone' => '0999900001',
        'role'      => 'parent',
    ]);
    $u2 = App\Services\AutoUserCreator::forProfile([
        'nom'       => 'U2', 'prenoms' => 'x',
        'email'     => $email, 'telephone' => '0999900002',
        'role'      => 'enseignant',
    ]);
    $ok = $u1 !== $u2 && $u1 > 0 && $u2 > 0;
    DB::table('users')->whereIn('id', [$u1, $u2])->delete();
    $results[] = ["Users email partage (2 users distincts)", $ok ? 'OK' : 'FAIL', "u1=$u1 u2=$u2"];
} catch (\Throwable $e) {
    $results[] = ["Users email partage", 'FAIL', $e->getMessage()];
}

// ---------- 7. Routes existent ----------
$routes = [
    'parametrage.categorie_apprenants.index',
    'parametrage.categorie_apprenants.create',
    'parametrage.categorie_apprenants.store',
    'parametrage.categorie_apprenants.edit',
    'parametrage.periodes_colaires.store',
    'academique.apprenants.store',
    'academique.enseignants.store',
];
foreach ($routes as $r) {
    $exists = Route::has($r);
    $results[] = ["Route: $r", $exists ? 'OK' : 'FAIL', ''];
}

// ---------- 8. Entity CategorieApprenant instanciable ----------
try {
    $inst = new Modules\Parametrage\Entities\CategorieApprenant();
    $ok = $inst->getTable() === 'categorie_apprenants';
    $results[] = ['CategorieApprenant entity table', $ok ? 'OK' : 'FAIL', $inst->getTable()];
} catch (\Throwable $e) {
    $results[] = ['CategorieApprenant entity table', 'FAIL', $e->getMessage()];
}

// ---------- Render ----------
echo str_pad("TEST", 55, ' ') . str_pad("STATUS", 10, ' ') . "DETAILS\n";
echo str_repeat('-', 100) . "\n";
$fails = 0;
foreach ($results as [$name, $status, $detail]) {
    if ($status === 'FAIL') $fails++;
    echo str_pad($name, 55, ' ') . str_pad($status, 10, ' ') . $detail . "\n";
}
echo str_repeat('-', 100) . "\n";
echo count($results) . " tests, " . (count($results) - $fails) . " OK, $fails FAIL\n";
exit($fails > 0 ? 1 : 0);
