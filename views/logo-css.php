/* only show the logos when width is >= 600px */
@media (min-width: 600px) {
    form.entity button {
        padding-left: 100px;
        background-repeat: no-repeat;
        background-position: 1em 50%;
        height: 75px;
    }

    /* a list of all IdP logos to be used as backgrounds for the buttons */
<?php foreach ($idpInfoList as $idpInfo): ?>
    form.entity button.<?=$this->e($idpInfo->getCssEncodedEntityId()); ?> {
        background-image: url("<?=$this->e($idpInfo->getEncodedEntityId()); ?>.png");
    }
<?php endforeach; ?>
}
