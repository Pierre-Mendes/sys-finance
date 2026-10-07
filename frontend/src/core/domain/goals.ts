/** O aporte faz a meta cruzar o alvo agora (não comemora meta que já estava concluída). */
export const contributionReachesGoal = (accumulated: number, target: number, contribution: number): boolean =>
  target > 0 && contribution > 0 && accumulated < target && accumulated + contribution >= target
