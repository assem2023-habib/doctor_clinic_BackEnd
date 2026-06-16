export function randomItem(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export function randomSleep(min = 1, max = 3) {
  const ms = (Math.random() * (max - min) + min) * 1000;
  return ms;
}

export function uuidv4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export function pickRandomToken(tokens) {
  const emails = Object.keys(tokens);
  const email = randomItem(emails);
  return {
    email,
    access_token: tokens[email].access_token,
    refresh_token: tokens[email].refresh_token,
  };
}

export function pickRandomByRole(tokens, rolePrefix) {
  const emails = Object.keys(tokens).filter((e) => e.startsWith(rolePrefix));
  if (emails.length === 0) return null;
  const email = randomItem(emails);
  return {
    email,
    access_token: tokens[email].access_token,
    refresh_token: tokens[email].refresh_token,
  };
}
