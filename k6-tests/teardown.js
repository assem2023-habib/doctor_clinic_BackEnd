export function teardown(data) {
  if (data && data.ready) {
    console.log(`K6: Test finished. Started at: ${data.startedAt}`);
  }
}
