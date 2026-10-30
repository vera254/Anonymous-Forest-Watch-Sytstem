The Forest Watch System is a decentralized, open-source platform designed for environmental monitoring, threat detection, and ecological data collection. The Anonymous Edition is a specialized fork built for researchers, journalists, and activists operating in environments where operational security (OpSec) and data integrity are paramount.

This system allows users to crowdsource data on illegal logging, wildlife trafficking, and pollution without exposing their identities or locations. It utilizes a zero-knowledge architecture and routes all traffic through decentralized networks to ensure that neither the system administrators nor external observers can identify the contributors.

✨ Key Features
Anonymized Reporting: Submit geo-tagged reports (photos, audio, text) without linking them to a user account or IP address.

Tor & I2P Integration: Native support for routing traffic through The Onion Router (Tor) and Invisible Internet Project (I2P) to mask origin IPs.

End-to-End Encryption (E2EE): All data is encrypted on the client side before transmission. Only the intended recipient (or the public, if chosen) can decrypt it.

Decentralized Storage: Uses InterPlanetary File System (IPFS) for storing media, ensuring data resilience and censorship resistance.

Metadata Stripping: Automatic removal of EXIF data (GPS coordinates embedded in photos) to prevent accidental identity leaks.

Offline-First: Capable of storing data locally and syncing only when a secure connection is established.

Plausible Deniability: No persistent logs of user activity are kept on the server side.

🛠️ Technical Architecture
The Forest Watch System (Anonymous) is built on a "Trust No One" model.

Client Layer: A Progressive Web App (PWA) that runs in the browser (Tails/Whonix compatible). It handles local encryption and metadata scrubbing.

Transport Layer: Traffic is forced through the Tor network via SOCKS5 proxies.

Storage Layer: Encrypted blobs are pinned to a private IPFS cluster.

Indexing Layer: A minimal, anonymous database (SQLite or similar) that stores only hashed references to content, not the content itself.
