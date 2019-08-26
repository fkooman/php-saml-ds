<?php $this->layout('base'); ?>

<?php $this->start('header'); ?>
        <strong>Error</strong>
<?php $this->stop(); ?>

<?php $this->start('main'); ?>
    <h2>Error <?=$this->e($errorCode); ?></h2>

    <p>
        <?=$this->e($errorMessage); ?>
    </p>
<?php $this->stop(); ?>
