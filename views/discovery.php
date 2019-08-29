<?php $this->layout('base'); ?>
<?php $this->start('header'); ?>
    Select your organization to login to <strong><?=$this->e($displayName); ?></strong>
<?php $this->stop('header'); ?>
<?php $this->start('main'); ?>
    <form class="filter" method="get">
        <input type="hidden" name="returnIDParam" value="<?=$this->e($returnIDParam); ?>">
        <input type="hidden" name="entityID" value="<?=$this->e($entityID); ?>">
        <input type="hidden" name="return" value="<?=$this->e($return); ?>">
        <input type="text" name="filter" value="<?=$this->e($filter); ?>" autofocus="autofocus" autocomplete="off" placeholder="Search for your organization...">
    </form>

    <?php if (0 !== \count($idpList)): ?>
            <ul class="disco">
<?php foreach ($idpList as $idp): ?>
                <li data-keywords="<?=$this->e(\implode(' ', $idp['keywords'])); ?>">
                    <form method="post">
                        <input type="hidden" name="idpEntityID" value="<?=$this->e($idp['entityID']); ?>">
<?php if (null === $idp['displayName']): ?>
                        <input type="submit" value="<?=$this->e($idp['entityID']); ?>">
<?php else: ?>
                        <input type="submit" value="<?=$this->e($idp['displayName']); ?>">
<?php endif; ?>
                    </form>
                </li>
<?php endforeach; ?>
            </ul>
    <?php endif; ?>
<?php $this->stop('main'); ?>
