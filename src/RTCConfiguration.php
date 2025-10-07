<?php

/**
 * This file is part of the PHP WebRTC package.
 *
 * (c) Amin Yazdanpanah <https://www.aminyazdanpanah.com/#contact>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webrtc\Webrtc;

use Webrtc\Exception\InvalidArgumentException;
use Webrtc\ICE\Enum\TransportPolicyType;
use Webrtc\ICE\RTCIceServer;
use Webrtc\ICE\RTCIceServerInterface;

/**
 * The `RTCConfiguration` class provides configuration options for an
 * `RTCPeerConnection` class.
 *
 * This class allows configuration of ICE servers (STUN/TURN), certificate paths,
 * and private key paths for WebRTC connections. It implements the
 * `RTCConfigurationInterface` and provides methods to manage ICE servers and
 * security credentials.
 */
class RTCConfiguration implements RTCConfigurationInterface
{
    /**
     * Default STUN server URL used when no configuration is provided
     */
    private const string DEFAULT_STUN_SERVER = "stun:stun.l.google.com:19302";

    /**
     * Array of RTCIceServer objects representing STUN/TURN servers
     *
     * @var array<RTCIceServerInterface>
     */
    private array $iceServers = [];

    /**
     * Path to the certificate file for secure connections
     *
     * @var string|null
     */
    private ?string $certificatePath = null;

    /**
     * Path to the private key file for secure connections
     *
     * @var string|null
     */
    private ?string $privateKeyPath = null;

    private ?array $icePortRange = null;
    private ?TransportPolicyType $transportPolicy = null;

    /**
     * Constructs a new RTCConfiguration instance
     *
     * @param array|null $configuration Optional configuration array. If null,
     *        default configuration with a Google STUN server will be used.
     *        Expected keys:
     *        - 'iceServers': Array of ICE server configurations
     *        - 'certificatePath': Path to a certificate file (optional)
     *        - 'privateKeyPath': Path to a private key file (optional)
     * @throws InvalidArgumentException If iceServer configuration is invalid
     */
    public function __construct(?array $configuration = null)
    {
        $configuration !== null ? $this->parseConfiguration($configuration) : $this->getDefaultConfiguration();
    }

    /**
     * Gets all configured ICE servers
     *
     * @return array<RTCIceServerInterface> Array of configured ICE servers
     */
    public function getIceServers(): array
    {
        return $this->iceServers;
    }

    /**
     * Sets multiple ICE servers at once
     *
     * @param array<RTCIceServerInterface> $iceServers Array of ICE servers to add
     * @return void
     */
    public function setIceServers(array $iceServers): void
    {
        array_walk($iceServers, fn($iceServer) => $this->addIceServer($iceServer));
    }

    /**
     * Adds a single ICE server to the configuration
     *
     * @param RTCIceServerInterface $iceServer The ICE server to add
     * @return void
     */
    public function addIceServer(RTCIceServerInterface $iceServer): void
    {
        $this->iceServers[] = $iceServer;
    }

    /**
     * Gets the path to the certificate file
     *
     * @return string|null Path to the certificate file or null if not set
     */
    public function getCertificatePath(): ?string
    {
        return $this->certificatePath;
    }

    /**
     * Sets the path to the certificate file
     *
     * @param string|null $certificatePath Path to the certificate file or null to unset
     * @return void
     */
    public function setCertificatePath(?string $certificatePath): void
    {
        $this->certificatePath = $certificatePath;
    }

    /**
     * Gets the path to the private key file
     *
     * @return string|null Path to the private key file or null if not set
     */
    public function getPrivateKeyPath(): ?string
    {
        return $this->privateKeyPath;
    }

    /**
     * Sets the path to the private key file
     *
     * @param string|null $privateKeyPath Path to the private key file or null to unset
     * @return void
     */
    public function setPrivateKeyPath(?string $privateKeyPath): void
    {
        $this->privateKeyPath = $privateKeyPath;
    }

    /**
     * Parses a configuration array and sets up the ICE servers and paths
     *
     * @param array $configuration Configuration array with optional keys:
     *        - 'iceServers': Array of ICE server configurations (required if present)
     *        - 'certificatePath': Path to a certificate file (optional)
     *        - 'privateKeyPath': Path to a private key file (optional)
     * @return void
     * @throws InvalidArgumentException If iceServer configuration is missing required 'urls' key
     */
    private function parseConfiguration(array $configuration): void
    {
        if (isset($configuration['iceServers'])) {
            foreach ($configuration["iceServers"] as $iceServer) {
                if (!isset($iceServer["urls"])) {
                    throw new InvalidArgumentException("urls key is mandatory in iceServer");
                }

                $iceServerObj = new RTCIceServer();
                $iceServerObj->setUrls($iceServer["urls"]);
                $iceServerObj->setUsername($iceServer["username"] ?? null);
                $iceServerObj->setCredential($iceServer["credential"] ?? null);
                $iceServerObj->setCredentialType($iceServer["credentialType"] ?? null);

                $this->addIceServer($iceServerObj);
            }
        }

        $this->certificatePath = $configuration["certificatePath"] ?? null;
        $this->privateKeyPath = $configuration["privateKeyPath"] ?? null;
    }

    /**
     * Sets up default configuration with Google's public STUN server
     *
     * @return void
     */
    private function getDefaultConfiguration(): void
    {
        $iceServer  = new RTCIceServer();
        $iceServer->setUrls([self::DEFAULT_STUN_SERVER]);
        $this->addIceServer($iceServer);
    }

    public function getIcePortRange(): ?array
    {
        return $this->icePortRange;
    }

    public function setIcePortRange(int $minPort, int $maxPort): void
    {
        if ($maxPort- $minPort < 100) {
            throw new InvalidArgumentException("maxPort - minPort must be greater than 100");
        }
        $this->icePortRange = [$minPort, $maxPort];
    }

    public function getTransportPolicy(): ?TransportPolicyType
    {
        return $this->transportPolicy;
    }

    public function setTransportPolicy(?TransportPolicyType $transportPolicy): void
    {
        $this->transportPolicy = $transportPolicy;
    }
}