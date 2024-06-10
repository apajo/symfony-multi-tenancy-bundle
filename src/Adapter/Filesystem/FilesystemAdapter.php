<?php

namespace aPajo\MultiTenancyBundle\Adapter\Filesystem;

use aPajo\MultiTenancyBundle\Adapter\Dsn;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterInterface;
use aPajo\MultiTenancyBundle\Adapter\PropertyAdapterTrait;
use aPajo\MultiTenancyBundle\Exception\InvalidArgumentException;
use Gaufrette\Adapter;
use Gaufrette\Adapter\Ftp as FtpAdapter;
use Gaufrette\Filesystem;

class FilesystemAdapter extends Filesystem implements PropertyAdapterInterface
{
  use PropertyAdapterTrait;

  protected string $property = 'filesystem';
  protected $adapter;

  public function __construct(Adapter $adapter)
  {
    $this->adapter = $adapter ;
    parent::__construct($this->adapter);
  }

  /**
   * Switch the filesystem adapter based on the provided DSN.
   *
   * @param Dsn $dsn The Data Source Name (e.g., ftp://user:pass@host:21/path)
   * @return FtpAdapter The configured FTP adapter.
   * @throws InvalidArgumentException if the DSN is invalid.
   */
  public function doAdapt(Dsn $dsn): FtpAdapter
  {
    if (!$dsn->getScheme() || !$dsn->getHost() || !$dsn->getUser() || !$dsn->getPassword()) {
      throw new InvalidArgumentException('Invalid DSN provided.');
    }

    $this->adapter = new FtpAdapter(
      $dsn->getPath(),
      $dsn->getHost(),
      [
        'username' => $dsn->getUser(),
        'password' => $dsn->getPassword(),
        'port' => $dsn->getPort(21),
        'root' => $dsn->getPath('/'),
        'create' => true,
        'passive' => true,
        'ssl' => true,
        'timeout' => 5,
      ]
    );

    return $this->adapter;
  }
}
