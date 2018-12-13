<?php $this->layout('base', ['useLogos' => false]); ?>

<?php $this->start('header'); ?>
        <strong>Error</strong>
<?php $this->stop(); ?>

<?php $this->start('container'); ?>
    <h2>Error <?=$this->e($errorCode); ?></h2>

    <p>
        <?=$this->e($errorMessage); ?>
    </p>
<?php $this->stop(); ?>
