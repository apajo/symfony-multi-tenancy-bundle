<?php

namespace aPajo\MultiTenancyBundle\Command;

use aPajo\MultiTenancyBundle\Service\EnvironmentProvider;
use aPajo\MultiTenancyBundle\Service\Registry\AdapterRegistry;
use aPajo\MultiTenancyBundle\Service\TenantConfig;
use aPajo\MultiTenancyBundle\Service\TenantManager;
use Core\BaseBundle\Email\EmailFactoryTrait;
use Core\BaseBundle\Media\MediaManagerTrait;
use Core\TenantBundle\Entity\Tenant;
use Core\TenantBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Command for making the initial user for the system
 *
 * @author Andres Pajo <andres@apajo.ee>
 */
class TestCommand extends Command
{
  use MediaManagerTrait;
  use EmailFactoryTrait;

  public function __construct(
    protected EntityManagerInterface $em,
    protected AdapterRegistry        $registry,
    protected TenantManager          $tenantManager,
    protected EnvironmentProvider    $environmentProvider,
    protected TenantConfig          $tenantConfig,
    protected MessageBusInterface $bus,
  )
  {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
      ->setName('tenant:test');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->file($input, $output);
    // $this->email($input, $output);

    return 0;
  }

  protected function file(InputInterface $input, OutputInterface $output): int
  {
    $file = $this->createTempFile();
    $tenant = $this->em->find(Tenant::class, 1);

    var_dump(
      $file,
      file_get_contents($file)
    );

    $this->environmentProvider->for($tenant, function (Tenant $tenant, EntityManagerInterface $em) use ($file) {
      dump($tenant->getKey());

      $media = $this->mediaManager->createMedia($file);
      dump($media->getName());

      $media2 = $this->mediaManager->find($media->getId());

      dump([
        '$media2',
        $media2->getName(),
      ]);
      dump($this->mediaManager->getMediaContent($media2));
    });

    return 0;
  }

  protected function createTempFile(): ?string
  {
    // Generate a unique temporary file name
    $tempFile = tempnam(sys_get_temp_dir(), 'tmp');

    if ($tempFile === false) {
      return null;
    }

    // Open the file for writing
    $fileHandle = fopen($tempFile, 'w');

    if ($fileHandle === false) {
      return null;
    }

    // Generate random content
    $randomContent = bin2hex(random_bytes(16)); // 32 characters of random hexadecimal content

    // Write the random content to the file
    fwrite($fileHandle, $randomContent);

    // Close the file
    fclose($fileHandle);

    // Return the name of the temporary file
    return $tempFile;
  }

  protected function email(InputInterface $input, OutputInterface $output): int
  {
    $file = $this->createTempFile();
    $tenant = $this->tenantConfig->getEntityManager()->find(Tenant::class, 1);
    $user = $this->em->find(User::class, 1);

    $this->environmentProvider->for($tenant, function (Tenant $tenant, EntityManagerInterface $em) use ($user) {
      $mail = $this->emailFactory->create('Test ttilte', 'andres@apajo.ee', 'Test email', [
        'html' => '<h1>Test email</h1>',
        'from' => $user,
        'attachments' => [],
      ]);

      $emailMessage = $this->emailFactory->addQueue($mail);
      // $this->bus->dispatch($emailMessage);

      $mail = $this->emailFactory->create('Test ttilte', 'andres@apajo.ee', 'Test email', [
        'html' => '<h1>Test email</h1>',
        'from' => $user,
        'attachments' => [],
      ]);

      $emailMessage = $this->emailFactory->addQueue($mail);
      // $this->bus->dispatch($emailMessage);
    });

    return 0;
  }

}
