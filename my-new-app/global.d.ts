// Global type declarations
declare global {
  // Define properties added to the global namespace
  var ngrokUrl: string | null;
  var localIpAddress: string | null;
  var useNgrok: boolean; // Add this new property
}

export {};
