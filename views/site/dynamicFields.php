<?php
use yii\web\View;

$this->registerJs('
    $(function () {
        $(\'[data-toggle="popover"]\').popover({
            container: \'body\'
            })
    });
    $("form").on("hidden.bs.modal", function () {
        // put your default event here
        $(\'[data-toggle="popover"]\').popover("hide");
    });
', View::POS_END);
?>
                    <button type="button" class="btn btn-link pull-right" data-toggle="popover" data-placement="right" 
                        data-html="true" 
                        data-title="Faire un copier-coller" 
                        data-content="<ul>
                            <li>#tous-les-participants# : <i><?= Yii::t('app', 'prénom et nom de chaque participant') ?></i></li>
                            <li>#prenom# : <i><?= Yii::t('app', 'prénom du client') ?></i></li>
                            <li>#nom# : <i><?= Yii::t('app', 'nom du client') ?></i></li>
                            <li>#nom-du-cours# : <i><?= Yii::t('app', 'nom du cours') ?></i></li>
                            <li>#jour-du-cours# : <i><?= Yii::t('app', 'jour de la semaine du cours') ?></i></li>
                            <li>#heure-debut# : <i><?= Yii::t('app', 'heure de début du cours') ?></i></li>
                            <li>#heure-fin# : <i><?= Yii::t('app', 'heure de fin du cours') ?></i></li>
                            <li>#salle-cours# : <i><?= Yii::t('app', 'salle concernée par le cours') ?></i></li>
                            <li>#nom-de-session# : <i><?= Yii::t('app', 'nom de la session') ?></i></li>
                            <li>#nom-de-saison# : <i><?= Yii::t('app', 'nom de la saison') ?></i></li>
                            <li>#prix-du-cours# : <i><?= Yii::t('app', 'prix du cours') ?></i></li>
                            <li>#date-prochain# : <i><?= Yii::t('app', 'date du prochain cours') ?></i></li>
                            <li>#toutes-les-dates#: <i><?= Yii::t('app', 'toutes les dates du cours') ?></i></li>
                            <li>#toutes-les-dates-avec-lieux# : <i><?= Yii::t('app', 'toutes les dates du cours avec lieux') ?></i></li>
                            <li>#dates-inscrit# : <i><?= Yii::t('app', 'date à laquelle le client est inscrit') ?></i></li>
                            <li>#dates-inscrit-avec-lieux# : <i><?= Yii::t('app', 'date à laquelle le client est inscrit avec lieux') ?></i></li>
                            <li>#statut-inscription# : <i><?= Yii::t('app', 'statut de l\'inscription du client') ?></i></li>
                        </ul>">
                            <?= Yii::t('app', 'Champs dynamiques disponibles') ?>
                    </button>
                    <br />