/* only show the logos when width is >= 600px */
@media (min-width: 600px) {
    form.entity button {
        padding-left: 100px;
        background-repeat: no-repeat;
        background-position: 1em 50%;
        height: 75px;
    }

    /* a list of all IdP logos to be used as backgrounds for the buttons */
<?php foreach ($entityDescriptors as $entityDescriptor): ?>
    form.entity button.<?=$this->e($entityDescriptor['cssEncodedEntityID']); ?> {
        background-image: url("<?=$this->e($entityDescriptor['encodedEntityID']); ?>.png");
    }
<?php endforeach; ?>
}
