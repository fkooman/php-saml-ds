<?php $this->layout('base', ['useLogos' => $useLogos, 'encodedEntityID' => $encodedEntityID, 'mTime' => $mTime]); ?>

<?php $this->start('header'); ?>
    Select an institution to login to <span class="serviceName"><?=$this->e($displayName); ?></span>
<?php $this->stop(); ?>
    
<?php $this->start('container'); ?>
    <form class="filter" method="get">
        <input type="hidden" name="returnIDParam" value="<?=$this->e($returnIDParam); ?>">
        <input type="hidden" name="entityID" value="<?=$this->e($entityID); ?>">
        <input type="hidden" name="return" value="<?=$this->e($return); ?>">

        <input <?php if (!empty($lastChosenList)): ?> autofocus="autofocus"<?php endif; ?> value="<?=$this->e($filter); ?>" name="filter" id="filter" tabindex="1" type="text" autocomplete="off" placeholder="Search for an institution...">
    </form>

    <?php if (!empty($lastChosenList)): ?>
        <div id="lastChosen">
            <div class="listHeader">Previously chosen</div>
            <ul>
                <?php foreach ($lastChosenList as $key => $idp): ?>
                <li>
                    <form class="entity" method="post">
                        <button class="<?=$this->e($idp['encodedEntityID']); ?>" <?php if (0 === $key): ?>autofocus="autofocus"<?php endif; ?> name="idpEntityID" value="<?=$this->e($idp['entityID']); ?>" tabindex="<?=$this->e($key + 2); ?>"><?=$this->e($idp['displayName']); ?></button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($idpList)): ?>
        <div id="accessList">
            <div class="listHeader">Institutes with access</div>
            <ul id="disco">
                <?php foreach ($idpList as $key => $idp): ?>
                    <li>
                        <form class="entity" method="post">
                            <button <?php if ($filter && 0 === $key): ?>autofocus="autofocus"<?php endif; ?> name="idpEntityID" value="<?=$this->e($idp['entityID']); ?>" tabindex="<?=$key + \count($lastChosenList) + 2; ?>" class="<?=$this->e($idp['encodedEntityID']); ?>" data-keywords="<?=$this->e(\implode(' ', $idp['keywords'])); ?>"><?=$this->e($idp['displayName']); ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php $this->stop(); ?>
