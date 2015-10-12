<?php

namespace App\Model\Domain\Entities;

use App\Model\Authorization\IRole;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Utils\Validators;
use DateTime;
use Tracy\Debugger;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="user",
 *      options={"collate": "utf8_czech_ci"},
 *      indexes={
 *          @Index(name="role_username", columns={"role", "username"}),
 *          @Index(name="is_closed", columns={"is_closed"})
 *      }
 * )
 */
class User extends Entity implements IIdentity, IRole
{
    use Identifier;

    /**
     * @ORM\Column(name="username", type="string", length=25, nullable=false, unique=true)
     * @var string
     */
    protected $username;

    /**
     * @ORM\Column(name="password", type="string", length=60, nullable=false, unique=false, options={"fixed": true})
     * @var string
     */
    protected $password;

    /**
     * @ORM\Column(name="email", type="string", length=70, nullable=false, unique=true)
     * @var string
     */
    protected $email;
    
    /**
     * @ORM\Column(name="name", type="string", length=70, nullable=true, unique=false)
     * @var string
     */
    protected $name;
    
    /**
     * @ORM\Column(name="role", type="string", length=20, nullable=false, unique=false)
     * @var string
     */
    private $role;
    
    /**
     * @ORM\Column(name="ip", type="string", length=39, nullable=false, unique=false)
     * @var string
     */
    protected $ip;
    
    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=false, unique=false)
     * @var DateTime
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="last_ip", type="string", length=39, nullable=false, unique=false)
     * @var string
     */
    protected $lastIp;

    /**
     * @ORM\Column(name="token", type="string", length=32, nullable=true, unique=false)
     * @var string
     */
    private $token;
    
    /**
     * @ORM\Column(name="token_validity", type="datetime", nullable=true, unique=false)
     * @var DateTime
     */
    private $tokenValidity;

    /**
     * The one who invited this user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="host", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @var User
     */
    private $host;

    /**
     * @ORM\Column(name="is_closed", type="boolean", nullable=false, unique=false, options={"default": false})
     * @var bool
     */
    private $isClosed;

    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="usersBlockingMe", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *      name="user_relationship_restriction",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="blocked_user_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $usersBlockedByMe;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="usersBlockedByMe", fetch="EXTRA_LAZY")
     */
    private $usersBlockingMe;

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $ip
     * @param User $host
     * @param string $role
     * @param string|null $name
     * @return User
     */
    public function __construct(
        $username,
        $password,
        $email,
        $ip,
        User $host = null,
        $role = 'employee',
        $name = null
    ) {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $this->setIp($ip);
        $this->setRole($role);
        $this->setName($name);
        $this->setLastIP($ip);
        $this->setLastLogin(new DateTime());

        $this->usersBlockedByMe = new ArrayCollection;
        $this->usersBlockingMe = new ArrayCollection;

        $this->host = $host;
        $this->isClosed = false;
    }

    public function getAllMessages()
    {
        return $this->myMessages->toArray();
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $username = $this->processString($username);

        Validators::assert($username, 'unicode:1..25');
        $this->username = $username;
    }

    /**
     * Method also hashes password
     * @param string $password
     */
    public function setPassword($password)
    {
        $password = $this->processString($password);
        Validators::assert($password, 'string:5..');

        $password = Passwords::hash(trim($password));
        $this->password = $password;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        Validators::assert($email, 'email');
        $this->email = $email;
    }

    /**
     * @param null|string $name
     */
    public function setName($name)
    {
        $name = $this->processString($name);

        Validators::assert($name, 'unicode:..70|null');
        $this->name = $name;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $role = $this->processString($role);

        Validators::assert($role, 'unicode:1..20');
        $this->role = $role;
    }

    /**
     * @param $ip
     * @return string|false
     */
    private function validateIPAddress($ip)
    {
        $ip = filter_var($ip, FILTER_VALIDATE_IP, ['flags' => [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6]]);

        return $ip;
    }

    /**
     * @param string $ip
     */
    private function setIp($ip)
    {
        $this->ip = $this->validateIPAddress($ip);
    }

    /**
     * @param DateTime $lastLogin
     */
    public function setLastLogin(DateTime $lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @param string $lastIP
     */
    public function setLastIP($lastIP)
    {
        $this->lastIp = $this->validateIPAddress($lastIP);
    }

    public function createToken()
    {
        $this->token = \Nette\Utils\Random::generate(32);

        $currentDay = new \DateTime();
        $tokenValidity = $currentDay->modify('+1 day');

        $this->tokenValidity = $tokenValidity;
    }

    public function resetToken()
    {
        $this->token = null;
        $this->tokenValidity = null;
    }

    public function toggleAccessibility()
    {
        $this->isClosed = $this->isClosed == true ? false : true;
    }

    /**
     * @return bool
     */
    public function isUserAccountAccessible()
    {
        return $this->isClosed == false;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return DateTime
     */
    public function getTokenValidity()
    {
        return $this->tokenValidity;
    }

    /**
     * @return User
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return [$this->role];
    }

    public function isInRole($role)
    {
        return $this->role === $role;
    }

    public function blockUser(User $user)
    {
        if (!$this->usersBlockedByMe->contains($user)) {
            $this->usersBlockedByMe[] = $user;
        }
    }

    public function unblockUser(User $user)
    {
        if ($this->usersBlockedByMe->contains($user)) {
            $this->usersBlockedByMe->removeElement($user);
        }
    }

    /**
     * @return array
     */
    public function getUsersBlockedByMe()
    {
        return $this->usersBlockedByMe->toArray();
    }

    public function getUsersBlockingMe()
    {
        return $this->usersBlockingMe->toArray();
    }

    /* ************************** */

    /**
     * Returns a string identifier of the Role.
     * @return string
     */
    function getRoleId()
    {
        return $this->role;
    }


}