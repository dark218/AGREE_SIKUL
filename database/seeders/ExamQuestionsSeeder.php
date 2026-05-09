<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExamQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('questions_examen')->count() > 0) {
            $this->command->info('Questions existent deja, skip.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $now = now();

        $examQuestions = [
            // Examen 1: Maths
            1 => [
                ['Quel est le resultat de 7 x 8 ?', 'qcm', 2, [['56', true], ['54', false], ['48', false], ['64', false]]],
                ['Simplifier 12/16', 'qcm', 2, [['3/4', true], ['2/3', false], ['4/5', false], ['6/8', false]]],
                ['Resoudre : 2x + 5 = 15', 'qcm', 2, [['x = 5', true], ['x = 10', false], ['x = 7', false], ['x = 3', false]]],
                ['La racine carree de 144 est :', 'qcm', 2, [['12', true], ['14', false], ['11', false], ['13', false]]],
                ['Quel est le perimetre d\'un cercle de rayon 7 cm ?', 'qcm', 2, [['43.96 cm', true], ['44 cm', false], ['21.98 cm', false], ['154 cm', false]]],
                ['Un triangle rectangle a des cotes 3 et 4. L\'hypotenuse mesure :', 'qcm', 2, [['5', true], ['7', false], ['6', false], ['8', false]]],
                ['Combien vaut pi approximativement ?', 'qcm', 1, [['3.14', true], ['3.41', false], ['2.14', false], ['4.14', false]]],
                ['1/2 + 1/3 = ?', 'qcm', 2, [['5/6', true], ['2/5', false], ['3/6', false], ['1/5', false]]],
                ['Tout nombre multiplie par 0 donne 0', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Un carre a 4 angles droits', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
            ],
            // Examen 2: Physique
            2 => [
                ['Quelle est l\'unite de la force ?', 'qcm', 2, [['Newton (N)', true], ['Joule (J)', false], ['Watt (W)', false], ['Pascal (Pa)', false]]],
                ['La vitesse de la lumiere est environ :', 'qcm', 2, [['300 000 km/s', true], ['150 000 km/s', false], ['3 000 km/s', false], ['1 000 000 km/s', false]]],
                ['F = m x a est la loi de :', 'qcm', 2, [['Newton', true], ['Einstein', false], ['Galilee', false], ['Archimede', false]]],
                ['L\'eau bout a 100 degres Celsius', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['La gravite terrestre est environ 9.8 m/s2', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Quel instrument mesure la tension electrique ?', 'qcm', 2, [['Voltmetre', true], ['Amperemetre', false], ['Ohmmetre', false], ['Wattmetre', false]]],
                ['L\'unite de resistance electrique est :', 'qcm', 2, [['Ohm', true], ['Ampere', false], ['Volt', false], ['Watt', false]]],
                ['Quelle est la formule de l\'energie cinetique ?', 'qcm', 2, [['Ec = 1/2 mv2', true], ['Ec = mgh', false], ['Ec = Fd', false], ['Ec = mc2', false]]],
            ],
            // Examen 3: Francais
            3 => [
                ['Quel est le pluriel de "cheval" ?', 'qcm', 2, [['chevaux', true], ['chevals', false], ['chevales', false], ['chevaulx', false]]],
                ['Le passe compose de "aller" (je) est :', 'qcm', 2, [['je suis alle', true], ['j\'ai alle', false], ['je fus alle', false], ['j\'allais', false]]],
                ['Un synonyme de "grand" est :', 'qcm', 1, [['immense', true], ['petit', false], ['court', false], ['bas', false]]],
                ['L\'adjectif s\'accorde en genre et en nombre', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['"Les Bouts de bois de Dieu" est de :', 'qcm', 2, [['Ousmane Sembene', true], ['Leopold Sedar Senghor', false], ['Mariama Ba', false], ['Cheikh Anta Diop', false]]],
                ['Le COD repond a la question "qui ?" ou "quoi ?"', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Quel est le contraire de "genereux" ?', 'qcm', 1, [['avare', true], ['gentil', false], ['riche', false], ['pauvre', false]]],
                ['Un verbe du 2eme groupe se termine en :', 'qcm', 2, [['ir (issons)', true], ['er', false], ['re', false], ['oir', false]]],
                ['Conjuguez "finir" au present : nous ___', 'qcm', 2, [['finissons', true], ['finons', false], ['finissent', false], ['finirons', false]]],
                ['"Une si longue lettre" est un roman epistolaire', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
            ],
            // Examen 4: Anglais
            4 => [
                ['What is the past tense of "go" ?', 'qcm', 2, [['went', true], ['goed', false], ['gone', false], ['goes', false]]],
                ['"She ___ to school every day."', 'qcm', 2, [['goes', true], ['go', false], ['going', false], ['gone', false]]],
                ['What is the plural of "child" ?', 'qcm', 2, [['children', true], ['childs', false], ['childrens', false], ['childes', false]]],
                ['English is spoken in the UK', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Translate: "Je suis etudiant"', 'qcm', 2, [['I am a student', true], ['I is student', false], ['I are student', false], ['Me student', false]]],
                ['"How old are you?" means:', 'qcm', 1, [['Quel age as-tu ?', true], ['Comment vas-tu ?', false], ['Ou habites-tu ?', false], ['Qui es-tu ?', false]]],
                ['The capital of England is:', 'qcm', 1, [['London', true], ['Paris', false], ['New York', false], ['Dublin', false]]],
                ['"Beautiful" is an adjective', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
            ],
            // Examen 5: SVT
            5 => [
                ['Quel organe pompe le sang ?', 'qcm', 2, [['Le coeur', true], ['Le foie', false], ['Les poumons', false], ['Le cerveau', false]]],
                ['La photosynthese produit :', 'qcm', 2, [['du glucose et de l\'oxygene', true], ['du CO2', false], ['de l\'azote', false], ['de l\'eau uniquement', false]]],
                ['L\'ADN se trouve dans :', 'qcm', 2, [['le noyau cellulaire', true], ['le cytoplasme', false], ['la membrane', false], ['le ribosome', false]]],
                ['Les plantes respirent la nuit', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Le sang est filtre par :', 'qcm', 2, [['les reins', true], ['le foie', false], ['les poumons', false], ['l\'estomac', false]]],
                ['Un mammifere allaite ses petits', 'vrai_faux', 1, [['Vrai', true], ['Faux', false]]],
                ['Combien de chromosomes a l\'etre humain ?', 'qcm', 2, [['46', true], ['48', false], ['44', false], ['23', false]]],
                ['La mitose produit :', 'qcm', 2, [['2 cellules identiques', true], ['4 cellules', false], ['1 cellule', false], ['3 cellules', false]]],
            ],
        ];

        $totalQ = 0;
        $totalR = 0;

        foreach ($examQuestions as $examId => $questions) {
            foreach ($questions as $ordre => $q) {
                $qId = DB::table('questions_examen')->insertGetId([
                    'examen_en_ligne_id' => $examId,
                    'ordre' => $ordre + 1,
                    'type' => $q[1],
                    'enonce' => $q[0],
                    'points' => $q[2],
                    'obligatoire' => 1,
                    'etat' => 'actif',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $totalQ++;

                foreach ($q[3] as $rOrdre => $rep) {
                    DB::table('reponses_question')->insert([
                        'question_examen_id' => $qId,
                        'ordre' => $rOrdre + 1,
                        'texte' => $rep[0],
                        'est_correcte' => $rep[1] ? 1 : 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $totalR++;
                }
            }

            // Mettre a jour nombre_questions
            DB::table('examens_en_ligne')->where('id', $examId)->update([
                'nombre_questions' => count($questions),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info("✅ $totalQ questions + $totalR reponses creees pour 5 examens");
    }
}
