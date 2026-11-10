<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use Throwable;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Translation\LocaleContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Concrete\Core\Page\Controller\DashboardPageController;
use EventManagerKit\Import\{CsvImporter, ImportPersister};

class Import extends DashboardPageController
{
    public function on_start()
    {
        parent::on_start();
        $this->set('locales', LocaleContext::siteLocales());
        $this->set('maxUploadMb', round(UploadedFile::getMaxFilesize() / 1024 / 1024, 1));
    }

    public function view() {}

    public function run_import()
    {
        if (!$this->token->validate('em-import')) {
            $this->error->add($this->token->getErrorMessage());
        }

        $file = $this->request->files->get('csv_file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            $this->error->add(t('Please choose a CSV file to upload.'));
        } elseif (strtolower($file->getClientOriginalExtension()) !== 'csv') {
            $this->error->add(t('The file must be a .csv file.'));
        } elseif ($file->getSize() > ($max = UploadedFile::getMaxFilesize())) {
            $this->error->add(t('The file is too large (limit %s MB).', round($max / 1024 / 1024, 1)));
        }

        if (!$this->error->has()) {
            $handle = fopen($file->getPathname(), 'r');
            $plan = (new CsvImporter())->parse($handle, LocaleContext::siteLocales());
            fclose($handle);

            $this->set('errors', $plan->errors);
            $this->set('validRows', count($plan->rows));

            if ($plan->errors) {
                // All or nothing: a file with any bad row imports nothing.
                $this->error->add(t('The file has problems and nothing was imported. Fix the rows listed below and upload it again.'));
            } elseif ($this->request->request->get('validate_only')) {
                $this->flash('success', t('The file is valid: %d event(s) would be imported.', count($plan->rows)));
            } else {
                try {
                    $summary = (new ImportPersister($this->app->make(EntityManagerInterface::class)))->persist($plan);
                    $this->flash('success', t('Imported %d event(s); skipped %d already present.', $summary['created'], $summary['skipped']));

                    return $this->buildRedirect('/dashboard/event_manager/events')->send();
                } catch (Throwable $e) {
                    $this->error->add(t('The import failed and was rolled back: %s', $e->getMessage()));
                }
            }
        }

        $this->view();
    }
}
