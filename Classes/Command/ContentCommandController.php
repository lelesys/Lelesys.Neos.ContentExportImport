<?php
namespace Lelesys\Neos\ContentExportImport\Command;

/*
 * This file is part of the Lelesys.Neos.ContentExportImport package.
 */

use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeExportService;
use Neos\ContentRepository\Domain\Service\ImportExport\NodeImportService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Exception as NeosException;
use Neos\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class ContentCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     *
     * @var NodeExportService
     */
    protected $nodeExportService;

    /**
     * @Flow\Inject
     * @var NodeImportService
     */
    protected $nodeImportService;

    /**
     * Export node tree from given source node to XML
     *
     * @param string $siteNodeName Site node name
     * @param string $sourceNodeIdentifier Node identifier of starting point node
     * @param string $filename Filename to write XML to. Resources directory will be created at same path of Filename
     * @param boolean $tidy
     * @param string $nodeTypeFilter
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function exportCommand($siteNodeName, $sourceNodeIdentifier, $filename, $tidy = true, $nodeTypeFilter = null)
    {
        $site = $this->siteRepository->findOneByNodeName($siteNodeName);
        if ($site === null) {
            $this->outputLine('<error>No site with node name "%s" found</error>', array($siteNodeName));
            $this->quit(1);
        }

        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create([
            'currentSite' => $site,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        $sourceNode = $contentContext->getNodeByIdentifier($sourceNodeIdentifier);
        if ($sourceNode === null) {
            $this->outputLine('<error>No node with identifier "%s" found</error>', array($sourceNodeIdentifier));
            $this->quit(1);
        }

        $resourcesPath = Files::concatenatePaths([dirname($filename), 'Resources']);
        Files::createDirectoryRecursively($resourcesPath);


        $xmlWriter = new \XMLWriter();
        $xmlWriter->openUri($filename);
        $xmlWriter->setIndent($tidy);
        $xmlWriter->startDocument('1.0', 'UTF-8');

        $this->nodeExportService->export($sourceNode->getPath(), $contentContext->getWorkspaceName(), $xmlWriter, $tidy, true, $resourcesPath, $nodeTypeFilter);
        $xmlWriter->flush();
        $this->outputLine('Export finished');
    }

    /**
     * Import node tree from given XML file into target node
     *
     * @param string $siteNodeName Site node name
     * @param string $targetNodeIdentifier Target node identifier under which the node tree will be imported
     * @param string $filename Filename to read XML from
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function importCommand($siteNodeName, $targetNodeIdentifier, $filename)
    {
        if (! is_file($filename)) {
            $this->outputLine('<error>File "%s" not found</error>', array($filename));
            $this->quit(1);
        }

        $site = $this->siteRepository->findOneByNodeName($siteNodeName);
        if ($site === null) {
            $this->outputLine('<error>No site with node name "%s" found</error>', array($siteNodeName));
            $this->quit(1);
        }

        /** @var ContentContext $contentContext */
        $contentContext = $this->contextFactory->create([
            'currentSite' => $site,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);

        $targetNode = $contentContext->getNodeByIdentifier($targetNodeIdentifier);
        if ($targetNode === null) {
            $this->outputLine('<error>No node with identifier "%s" found</error>', array($targetNodeIdentifier));
            $this->quit(1);
        }

        $xmlReader = new \XMLReader();
        if ($xmlReader->open($filename, null, LIBXML_PARSEHUGE) === false) {
            $this->outputLine('<error>Error: XMLReader could not open "%s"</error>', array($filename));
            $this->quit(1);
        }
        $this->nodeImportService->import($xmlReader, $targetNode->getPath(), dirname($filename) . '/Resources');
        $this->outputLine('Import finished');
    }
}
