<?php
/*
 * This file is part of the Recommend Product plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Recommend;

use Eccube\Common\Constant;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Util\Cache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager
 * @package Plugin\Recommend
 */
class PluginManager extends AbstractPluginManager
{

    /**
     * @var string コピー元ブロックファイル
     */
    private $originBlock;

    /**
     * @var string ブロック名
     */
    private $blockName = 'おすすめ商品';

    /**
     * @var string ブロックファイル名
     */
    private $blockFileName = 'recommend_product_block';

    /**
     * @var string コピー元リソースディレクトリ
     */
    private $origin;

    /**
     * @var string コピー先リソースディレクトリ
     */
    private $target;

    /**
     * PluginManager constructor.
     */
    public function __construct()
    {
        // コピー元ブロックファイル
        $this->originBlock = __DIR__.'/Resource/template/Block/'.$this->blockFileName.'.twig';

        // コピー元のディレクトリ
        $this->origin = __DIR__.'/Resource/assets';

        // コピー先のディレクトリ
        $this->target = __DIR__ . '/../../../html/plugin/recommend';
    }

    /**
     * @param array  $config
     * @param \Eccube\Application $app
     */
    public function install($config, $app)
    {
        // リソースファイルのコピー
        $this->copyAssets();
    }

    /**
     * @param array  $config
     * @param object $app
     */
    public function uninstall($config, $app)
    {
        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code'], 0);
        // リソースファイルの削除
        $this->removeAssets();

        if (file_exists($app['config']['block_realdir'].'/'.$this->blockFileName.'.twig')) {
            $this->removeBlock($app);
        }

        // ブロックの削除
        $this->removeDataBlock($app);
    }

    /**
     * @param array  $config
     * @param \Eccube\Application $app
     */
    public function enable($config, $app)
    {
        $this->copyBlock($app);
        // ブロックへ登録
        $this->createDataBlock($app);

        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
    }

    /**
     * @param array  $config
     * @param \Eccube\Application $app
     */
    public function disable($config, $app)
    {
        $this->removeBlock($app);
        // ブロックの削除
        $this->removeDataBlock($app);
    }

    /**
     * @param array  $config
     * @param object $app
     */
    public function update($config, $app)
    {
        // リソースファイルのコピー
        $this->copyAssets();

        $this->copyBlock($app);

        $this->migrationSchema($app, __DIR__.'/Resource/doctrine/migration', $config['code']);
    }

    /**
     * ブロックを登録
     *
     * @param \Eccube\Application $app
     * @throws \Exception
     */
    private function createDataBlock($app)
    {
        $em = $app['orm.em'];
        try {
            $DeviceType = $app['eccube.repository.master.device_type']->find(DeviceType::DEVICE_TYPE_PC);

            /** @var \Eccube\Entity\Block $Block */
            $Block = $app['eccube.repository.block']->findOrCreate(null, $DeviceType);

            // Blockの登録
            $Block->setName($this->blockName)
                ->setFileName($this->blockFileName)
                ->setDeletableFlg(Constant::DISABLED)
                ->setLogicFlg(1);
            $em->persist($Block);
            $em->flush();

            // BlockPositionの登録
            $blockPos = $em->getRepository('Eccube\Entity\BlockPosition')->findOneBy(
                array('page_id' => 1, 'target_id' => PageLayout::TARGET_ID_MAIN_BOTTOM),
                array('block_row' => 'DESC')
            );

            $BlockPosition = new BlockPosition();

            // ブロックの順序を変更
            $BlockPosition->setBlockRow(1);
            if ($blockPos) {
                $blockRow = $blockPos->getBlockRow() + 1;
                $BlockPosition->setBlockRow($blockRow);
            }

            $PageLayout = $app['eccube.repository.page_layout']->find(1);

            $BlockPosition->setPageLayout($PageLayout)
                ->setPageId($PageLayout->getId())
                ->setTargetId(PageLayout::TARGET_ID_MAIN_BOTTOM)
                ->setBlock($Block)
                ->setBlockId($Block->getId())
                ->setAnywhere(Constant::ENABLED);

            $em->persist($BlockPosition);
            $em->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * ブロックを削除
     *
     * @param \Eccube\Application $app
     * @throws \Exception
     */
    private function removeDataBlock($app)
    {
        // Blockの取得(file_nameはアプリケーションの仕組み上必ずユニーク)
        /** @var \Eccube\Entity\Block $Block */
        $Block = $app['eccube.repository.block']->findOneBy(array('file_name' => $this->blockFileName));

        if (!$Block) {
            Cache::clear($app, false);

            return;
        }

        $em = $app['orm.em'];
        try {
            // BlockPositionの削除
            $blockPositions = $Block->getBlockPositions();
            /** @var \Eccube\Entity\BlockPosition $BlockPosition */
            foreach ($blockPositions as $BlockPosition) {
                $Block->removeBlockPosition($BlockPosition);
                $em->remove($BlockPosition);
            }

            // Blockの削除
            $em->remove($Block);
            $em->flush();
        } catch (\Exception $e) {
            throw $e;
        }

        Cache::clear($app, false);
    }

    /**
     * リソースファイル等をコピー
     */
    private function copyAssets()
    {
        $file = new Filesystem();
        $file->mirror($this->origin, $this->target.'/assets');
    }

    /**
     * コピーしたリソースファイルなどを削除
     */
    private function removeAssets()
    {
        $file = new Filesystem();
        $file->remove($this->target);
    }

    private function copyBlock($app)
    {
        // ファイルコピー
        $file = new Filesystem();
        // ブロックファイルをコピー
        $file->copy($this->originBlock, $app['config']['block_realdir'].'/'.$this->blockFileName.'.twig');
    }

    private function removeBlock($app)
    {
        $file = new Filesystem();
        $file->remove($app['config']['block_realdir'].'/'.$this->blockFileName.'.twig');
    }
}
