/**
 * Simple NetInfo polyfill for environments where the package isn't available
 */
const NetInfoPolyfill = {
  fetch: async () => {
    console.log("[NetInfo Polyfill] Returning default network state");
    return {
      isConnected: true,
      isInternetReachable: true,
      type: "unknown",
      details: null,
    };
  },
  addEventListener: () => {
    console.log(
      "[NetInfo Polyfill] addEventListener called, but not implemented"
    );
    return () => {}; // Return dummy unsubscribe function
  },
};

export default NetInfoPolyfill;
