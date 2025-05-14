# WebRTC In PHP

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

This package provides a complete WebRTC implementation written entirely in PHP, including support for ICE, DTLS, SRTP, SCTP, RTP, and data channels. Designed for real-time audio, video, and data communication, it enables peer-to-peer connectivity without relying on external WebRTC libraries. Built with modular components and fully asynchronous using ReactPHP.

##  Features

- Full peer-to-peer WebRTC stack in native PHP
- Implements ICE for NAT traversal and candidate negotiation
- Secure communication via DTLS and SRTP
- Reliable data transfer with SCTP and support for data channels
- RTP/RTCP handling for real-time audio and video streaming
- Modular design with support for custom signaling implementations
- Built on top of ReactPHP for asynchronous, non-blocking performance


## Requirements

- PHP ≥ 8.4 with FFI and GMP extension enabled
- OpenSSL development libraries
- Srtp development libraries
- Linux environment (Windows/macOS support planned)
- FFmpeg/libav shared libraries (libavcodec, libavfilter, etc.)
  - Compatible with FFmpeg **version 7.1.1**
- libopus development libraries
- libvpx development libraries
  - Compatible with libvpx **version 1.15.0**

## Documentation

This package is part of the PHP WebRTC library. For complete documentation, examples, and API reference, visit:

[PHP WebRTC Documentation](https://www.quasarstream.com/php-webrtc)

## Credits

### Authors

- **Amin Yazdanpanah**  
  - Website: [aminyazdanpanah.com](https://www.aminyazdanpanah.com)
  - Email: [github@aminyazdanpanah.com](mailto:github@aminyazdanpanah.com)

- **Sana Moniri**  
  - GtiHub: [sanamoniri](https://github.com/sanamoniri)

## Reporting Issues

Found a bug? Please report it on our [issues](https://github.com/php-webrtc/webrtc/issues).

## License

BSD 3-Clause License. See [LICENSE](LICENSE) for details.

## References

- [WebRTC Overview](https://webrtc.org/)
- [RFC 8829 – WebRTC API](https://datatracker.ietf.org/doc/html/rfc8829)
- [RFC 5245 – ICE](https://datatracker.ietf.org/doc/html/rfc5245)
- [RFC 5763 – DTLS-SRTP](https://datatracker.ietf.org/doc/html/rfc5763)
- [RFC 8831 – Data Channels](https://datatracker.ietf.org/doc/html/rfc8831)
