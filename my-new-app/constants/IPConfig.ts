// This file contains the IP address configuration for the mobile app
// Change this to your actual local network IP address when testing with mobile devices

// Your computer's local network IP address (typically starts with 192.168.x.x or 10.x.x.x)
// This needs to be your actual PC's network IP, not localhost or 127.0.0.1
// To find your IP address, run "ipconfig" in command prompt (Windows) or "ifconfig" in terminal (Mac/Linux)
export const LOCAL_IP_ADDRESS = "192.168.1.100"; // CHANGE THIS to your actual IP

// Methods to help get IP address programmatically (for future use)
export const detectIPAddress = (): string => {
  return LOCAL_IP_ADDRESS; // Currently just returns the manually set IP
};

export default { LOCAL_IP_ADDRESS };
