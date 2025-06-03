# WebRTC In PHP

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

This package provides a complete WebRTC implementation written entirely in PHP, including support for ICE, DTLS, SRTP, SCTP, RTP, and data channels. Designed for real-time audio, video, and data communication, it enables peer-to-peer connectivity without relying on external WebRTC libraries. Built with modular components and fully asynchronous using ReactPHP.

### Please Note:

We've been working on this repository and many other PHP WebRTC-related ones
(**such as ICE, RTP, RTCP, and more than 22 others**)
for a long time privately(in our git server) before making them open source(**We released our packages only after they have been fully tested and thoroughly debugged**).
Originally, there was a long commit history that reflected all our work.

However, we decided to **remove that history** in the initial public commit to **protect our privacy**. The original commits included details like our **working hours based on commit times and counts**, as well as our **personal email addresses**, which we did not feel comfortable sharing publicly.

**Removing the history helps us keep that information private and stay a bit safer from potential security risks.**


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
  - **Windows users:** Use [WSL](https://learn.microsoft.com/en-us/windows/wsl/install) or Docker Desktop.
      *Note: Native Windows support is planned for an upcoming releases (in a few months.).*

  - **macOS users:** Use an emulator like [UTM](https://mac.getutm.app/) or run the project using Docker.
  *Note: Native macOS support is coming in a few months.*
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
    Got it! Here's the revised version without em dashes and with a natural tone:

## Reporting Issues

Found a bug? Please report it on our [issues](https://github.com/php-webrtc/webrtc/issues).

## Contributors

Your contribution is crucial to our success, regardless of its size. We appreciate your support and encourage you to
read our **[CONTRIBUTING](https://github.com/php-webrtc/webrtc/blob/master/CONTRIBUTING.md)**
guide for detailed instructions on how to get involved. Together, we can make a significant impact.

## License

BSD 3-Clause License. See [LICENSE](LICENSE) for details.

## References

- [WebRTC Overview](https://webrtc.org/)
